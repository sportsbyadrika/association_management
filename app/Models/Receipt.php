<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Receipt extends Model
{
    protected string $table = 'receipts';

    protected array $fillable = [
        'association_id', 'member_id', 'income_head_id', 'project_id',
        'demand_id', 'amount', 'mode', 'bank_account_id', 'received_on',
        'remarks', 'created_by',
    ];

    /**
     * @param int|string $projectFilter '' = all, 'none' = no project, or a project id
     * @return array{data:list<array<string,mixed>>,total:int,page:int,perPage:int,pages:int}
     */
    public function paginateForAssociation(int $associationId, int $page = 1, int $perPage = 20, string $search = '', int|string $projectFilter = '', ?string $from = null, ?string $to = null): array
    {
        $where = 'WHERE r.association_id = ?';
        $params = [$associationId];
        if ($search !== '') {
            $where .= ' AND (m.name LIKE ? OR m.member_number LIKE ?)';
            $like = '%' . $search . '%';
            array_push($params, $like, $like);
        }
        if ($projectFilter === 'none') {
            $where .= ' AND r.project_id IS NULL';
        } elseif ($projectFilter !== '' && (int) $projectFilter > 0) {
            $where .= ' AND r.project_id = ?';
            $params[] = (int) $projectFilter;
        }
        if ($from !== null && $from !== '') {
            $where .= ' AND r.received_on >= ?';
            $params[] = $from;
        }
        if ($to !== null && $to !== '') {
            $where .= ' AND r.received_on <= ?';
            $params[] = $to;
        }

        $base = "SELECT r.*, m.name AS member_name, m.member_number, ih.name AS income_head_name,
                        p.name AS project_name, b.account_name AS bank_name
                 FROM receipts r
                 LEFT JOIN members m ON m.id = r.member_id
                 LEFT JOIN income_heads ih ON ih.id = r.income_head_id
                 LEFT JOIN projects p ON p.id = r.project_id
                 LEFT JOIN bank_accounts b ON b.id = r.bank_account_id
                 {$where}
                 ORDER BY r.received_on DESC, r.id DESC";
        $count = "SELECT COUNT(*) FROM receipts r LEFT JOIN members m ON m.id = r.member_id {$where}";
        return $this->paginateQuery($base, $count, $params, $page, $perPage);
    }

    /** @return list<array<string,mixed>> */
    public function forMember(int $memberId): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM receipts WHERE member_id = ? ORDER BY received_on ASC, id ASC',
            [$memberId]
        );
    }

    public function totalForMember(int $memberId): float
    {
        return (float) $this->db->fetchColumn(
            'SELECT COALESCE(SUM(amount),0) FROM receipts WHERE member_id = ?',
            [$memberId]
        );
    }

    public function totalForDemand(int $demandId): float
    {
        return (float) $this->db->fetchColumn(
            'SELECT COALESCE(SUM(amount),0) FROM receipts WHERE demand_id = ?',
            [$demandId]
        );
    }

    /**
     * Income grouped by income head and by project within a date range.
     * @return array{by_head:list<array<string,mixed>>,by_project:list<array<string,mixed>>,total:float}
     */
    public function incomeReport(int $associationId, ?string $from, ?string $to): array
    {
        [$dateWhere, $params] = $this->dateFilter($from, $to);
        $params = array_merge([$associationId], $params);

        $byHead = $this->db->fetchAll(
            "SELECT COALESCE(ih.name, 'Unspecified') AS head, COUNT(*) AS count, COALESCE(SUM(r.amount),0) AS total
             FROM receipts r
             LEFT JOIN income_heads ih ON ih.id = r.income_head_id
             WHERE r.association_id = ? {$dateWhere}
             GROUP BY r.income_head_id, ih.name
             ORDER BY total DESC",
            $params
        );
        $byProject = $this->db->fetchAll(
            "SELECT COALESCE(p.name, 'General / Subscription') AS project, COUNT(*) AS count, COALESCE(SUM(r.amount),0) AS total
             FROM receipts r
             LEFT JOIN projects p ON p.id = r.project_id
             WHERE r.association_id = ? {$dateWhere}
             GROUP BY r.project_id, p.name
             ORDER BY total DESC",
            $params
        );
        $total = (float) $this->db->fetchColumn(
            "SELECT COALESCE(SUM(amount),0) FROM receipts r WHERE r.association_id = ? {$dateWhere}",
            $params
        );
        return ['by_head' => $byHead, 'by_project' => $byProject, 'total' => $total];
    }

    /** @return list<array<string,mixed>> detailed receipts for report/CSV */
    public function detailReport(int $associationId, ?string $from, ?string $to): array
    {
        [$dateWhere, $params] = $this->dateFilter($from, $to);
        $params = array_merge([$associationId], $params);
        return $this->db->fetchAll(
            "SELECT r.received_on, m.name AS member_name, ih.name AS income_head_name,
                    p.name AS project_name, r.mode, r.amount, r.remarks
             FROM receipts r
             LEFT JOIN members m ON m.id = r.member_id
             LEFT JOIN income_heads ih ON ih.id = r.income_head_id
             LEFT JOIN projects p ON p.id = r.project_id
             WHERE r.association_id = ? {$dateWhere}
             ORDER BY r.received_on ASC, r.id ASC",
            $params
        );
    }

    /** @return array{0:string,1:list<string>} */
    private function dateFilter(?string $from, ?string $to): array
    {
        $where = '';
        $params = [];
        if ($from) {
            $where .= ' AND r.received_on >= ?';
            $params[] = $from;
        }
        if ($to) {
            $where .= ' AND r.received_on <= ?';
            $params[] = $to;
        }
        return [$where, $params];
    }
}
