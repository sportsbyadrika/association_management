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

    /** @return list<array<string,mixed>> */
    public function paginateForAssociation(int $associationId, int $page = 1, int $perPage = 20): array
    {
        $base = "SELECT d.*, m.name AS member_name, p.name AS project_name
                 FROM demands d
                 JOIN members m ON m.id = d.member_id
                 LEFT JOIN projects p ON p.id = d.project_id
                 WHERE d.association_id = ?
                 ORDER BY d.created_at DESC";
        $count = 'SELECT COUNT(*) FROM demands d WHERE d.association_id = ?';
        return $this->paginateQuery($base, $count, [$associationId], $page, $perPage);
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
}
