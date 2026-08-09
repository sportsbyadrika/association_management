<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class CommitteeOfficial extends Model
{
    protected string $table = 'committee_officials';

    protected array $fillable = [
        'association_id', 'committee_id', 'official_designation_id', 'member_id',
        'user_id', 'name', 'phone', 'email', 'address', 'photo_path', 'sort_order',
    ];

    /**
     * Officials of a committee with their designation and login email.
     * @return list<array<string,mixed>>
     */
    public function forCommittee(int $committeeId): array
    {
        return $this->db->fetchAll(
            "SELECT o.*, d.name AS designation, u.email AS login_email, u.role AS login_role
             FROM committee_officials o
             LEFT JOIN official_designations d ON d.id = o.official_designation_id
             LEFT JOIN users u ON u.id = o.user_id
             WHERE o.committee_id = ?
             ORDER BY o.sort_order ASC, d.name ASC, o.name ASC",
            [$committeeId]
        );
    }

    /** @return array<string,mixed>|null */
    public function findForAssociation(int $id, int $associationId): ?array
    {
        return $this->db->fetch(
            'SELECT * FROM committee_officials WHERE id = ? AND association_id = ?',
            [$id, $associationId]
        );
    }
}
