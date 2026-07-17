<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Demand extends Model
{
    protected string $table = 'demands';

    protected array $fillable = [
        'association_id', 'member_id', 'purpose', 'project_id', 'amount',
        'due_date', 'status', 'remarks', 'created_by',
    ];

    /**
     * @return array{data:list<array<string,mixed>>,total:int,page:int,perPage:int,pages:int}
     */
    public function paginateForAssociation(int $associationId, string $search = '', ?string $fyStart = null, ?string $fyEnd = null, int $page = 1, int $perPage = 20): array
    {
        $where = 'WHERE d.association_id = ?';
        $params = [$associationId];

        if ($search !== '') {
            $where .= ' AND (m.member_number LIKE ? OR m.name LIKE ? OR m.mobile LIKE ?)';
            $like = '%' . $search . '%';
            array_push($params, $like, $like, $like);
        }
        if ($fyStart !== null && $fyEnd !== null) {
            // Filter by the demand's due date, falling back to when it was raised.
            $where .= ' AND COALESCE(d.due_date, DATE(d.created_at)) BETWEEN ? AND ?';
            array_push($params, $fyStart, $fyEnd);
        }

        $base = "SELECT d.*, m.name AS member_name, m.member_number, m.mobile, p.name AS project_name,
                        (SELECT COALESCE(SUM(amount),0) FROM receipts WHERE demand_id = d.id) AS receipts_paid
                 FROM demands d
                 JOIN members m ON m.id = d.member_id
                 LEFT JOIN projects p ON p.id = d.project_id
                 {$where}
                 ORDER BY d.created_at DESC";
        $count = "SELECT COUNT(*) FROM demands d JOIN members m ON m.id = d.member_id {$where}";
        return $this->paginateQuery($base, $count, $params, $page, $perPage);
    }

    /**
     * Map of project_id => [member_id, ...] for members who already have an
     * active (non-cancelled) demand for that project. Used to optionally
     * exclude them when raising a new project demand.
     *
     * @return array<int,list<int>>
     */
    public function projectMemberMap(int $associationId): array
    {
        $rows = $this->db->fetchAll(
            "SELECT DISTINCT project_id, member_id FROM demands
             WHERE association_id = ? AND project_id IS NOT NULL AND status <> 'cancelled'",
            [$associationId]
        );
        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r['project_id']][] = (int) $r['member_id'];
        }
        return $map;
    }

    /** @return list<array<string,mixed>> */
    public function forMember(int $memberId): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM demands WHERE member_id = ? ORDER BY COALESCE(due_date, created_at) ASC, id ASC',
            [$memberId]
        );
    }

    public function totalForMember(int $memberId): float
    {
        return (float) $this->db->fetchColumn(
            "SELECT COALESCE(SUM(amount),0) FROM demands WHERE member_id = ? AND status <> 'cancelled'",
            [$memberId]
        );
    }

    /**
     * Recompute a demand's status from the receipts allocated to it:
     * paid (fully covered), partial (some paid) or pending (none).
     * Cancelled demands are left untouched.
     */
    public function syncStatus(int $demandId): void
    {
        $demand = $this->find($demandId);
        if ($demand === null || $demand['status'] === 'cancelled') {
            return;
        }
        $paid = (float) $this->db->fetchColumn(
            'SELECT COALESCE(SUM(amount),0) FROM receipts WHERE demand_id = ?',
            [$demandId]
        );
        $amount = (float) $demand['amount'];
        $status = $paid >= $amount ? 'paid' : ($paid > 0 ? 'partial' : 'pending');
        if ($status !== $demand['status']) {
            $this->db->run('UPDATE demands SET status = ? WHERE id = ?', [$status, $demandId]);
        }
    }
}
