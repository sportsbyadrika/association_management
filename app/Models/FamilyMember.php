<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class FamilyMember extends Model
{
    protected string $table = 'family_members';

    protected array $fillable = [
        'association_id', 'member_id', 'family_member_type_id', 'name', 'age',
        'gender', 'mobile', 'whatsapp', 'email', 'occupation', 'relation',
        'photo_path', 'notes', 'is_active',
    ];

    /**
     * Family members for a primary member, with their type name.
     * @return list<array<string,mixed>>
     */
    public function forMember(int $memberId): array
    {
        return $this->db->fetchAll(
            "SELECT fm.*, t.name AS type_name
             FROM family_members fm
             LEFT JOIN family_member_types t ON t.id = fm.family_member_type_id
             WHERE fm.member_id = ?
             ORDER BY fm.name ASC",
            [$memberId]
        );
    }

    /** Tenant-safe lookup. @return array<string,mixed>|null */
    public function findForAssociation(int $id, int $associationId): ?array
    {
        return $this->db->fetch(
            'SELECT * FROM family_members WHERE id = ? AND association_id = ?',
            [$id, $associationId]
        );
    }

    public function countForMember(int $memberId): int
    {
        return (int) $this->db->fetchColumn(
            'SELECT COUNT(*) FROM family_members WHERE member_id = ?',
            [$memberId]
        );
    }
}
