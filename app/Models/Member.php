<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Member extends Model
{
    protected string $table = 'members';

    protected array $fillable = [
        'association_id', 'member_type_id', 'name', 'age', 'gender', 'address',
        'mobile', 'whatsapp', 'email', 'family_members_count', 'occupation',
        'joined_on', 'photo_path', 'notes', 'is_active',
    ];

    /**
     * Paginated, searchable, sortable listing scoped to an association.
     * @return array{data:list<array<string,mixed>>,total:int,page:int,perPage:int,pages:int}
     */
    public function paginateForAssociation(int $associationId, string $search = '', string $sort = 'name', string $dir = 'asc', int $page = 1, int $perPage = 15): array
    {
        $allowedSort = ['name', 'created_at', 'age', 'mobile'];
        $sort = in_array($sort, $allowedSort, true) ? $sort : 'name';
        $dir = strtolower($dir) === 'desc' ? 'DESC' : 'ASC';

        $where = 'WHERE m.association_id = ?';
        $bindings = [$associationId];
        if ($search !== '') {
            $where .= ' AND (m.name LIKE ? OR m.mobile LIKE ? OR m.email LIKE ?)';
            $like = '%' . $search . '%';
            array_push($bindings, $like, $like, $like);
        }

        $base = "SELECT m.*, mt.name AS member_type_name
                 FROM members m
                 LEFT JOIN member_types mt ON mt.id = m.member_type_id
                 {$where}
                 ORDER BY m.{$sort} {$dir}";
        $count = "SELECT COUNT(*) FROM members m {$where}";

        return $this->paginateQuery($base, $count, $bindings, $page, $perPage);
    }

    /** @return array<string,mixed>|null */
    public function findWithType(int $id, int $associationId): ?array
    {
        return $this->db->fetch(
            "SELECT m.*, mt.name AS member_type_name
             FROM members m
             LEFT JOIN member_types mt ON mt.id = m.member_type_id
             WHERE m.id = ? AND m.association_id = ?",
            [$id, $associationId]
        );
    }

    public function countForAssociation(int $associationId): int
    {
        return (int) $this->db->fetchColumn(
            'SELECT COUNT(*) FROM members WHERE association_id = ? AND is_active = 1',
            [$associationId]
        );
    }

    /** For select dropdowns. @return list<array<string,mixed>> */
    public function options(int $associationId): array
    {
        return $this->db->fetchAll(
            'SELECT id, name FROM members WHERE association_id = ? AND is_active = 1 ORDER BY name ASC',
            [$associationId]
        );
    }
}
