<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Committee extends Model
{
    protected string $table = 'committees';

    protected array $fillable = [
        'association_id', 'name', 'start_date', 'end_date', 'is_active',
        'description',
    ];

    /** @return list<array<string,mixed>> */
    public function allWithCounts(int $associationId): array
    {
        return $this->db->fetchAll(
            "SELECT c.*,
                    (SELECT COUNT(*) FROM committee_officials o WHERE o.committee_id = c.id) AS official_count
             FROM committees c
             WHERE c.association_id = ?
             ORDER BY c.is_active DESC, c.start_date DESC, c.id DESC",
            [$associationId]
        );
    }

    /** @return array<string,mixed>|null */
    public function findForAssociation(int $id, int $associationId): ?array
    {
        return $this->db->fetch(
            'SELECT * FROM committees WHERE id = ? AND association_id = ?',
            [$id, $associationId]
        );
    }
}
