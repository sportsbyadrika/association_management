<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Member extends Model
{
    protected string $table = 'members';

    protected array $fillable = [
        'association_id', 'member_number', 'member_type_id', 'name', 'age',
        'gender', 'address', 'mobile', 'whatsapp', 'email',
        'family_members_count', 'occupation', 'joined_on', 'photo_path',
        'notes', 'is_active',
    ];

    /** Whether a member number is already used within the association. */
    public function memberNumberExists(int $associationId, string $memberNumber, ?int $ignoreId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM members WHERE association_id = ? AND member_number = ?';
        $params = [$associationId, $memberNumber];
        if ($ignoreId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $ignoreId;
        }
        return (int) $this->db->fetchColumn($sql, $params) > 0;
    }

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
            $where .= ' AND (m.member_number LIKE ? OR m.name LIKE ? OR m.mobile LIKE ? OR m.email LIKE ?)';
            $like = '%' . $search . '%';
            array_push($bindings, $like, $like, $like, $like);
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

    /**
     * Active members with the fields needed for the bulk-select table.
     * @return list<array<string,mixed>>
     */
    public function selectableForAssociation(int $associationId): array
    {
        return $this->db->fetchAll(
            'SELECT id, member_number, name, mobile
             FROM members WHERE association_id = ? AND is_active = 1
             ORDER BY name ASC',
            [$associationId]
        );
    }

    /**
     * Fetch a set of members by id, scoped to the association (tenant-safe).
     * @param list<int> $ids
     * @return list<array<string,mixed>>
     */
    public function findManyForAssociation(array $ids, int $associationId): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn ($v) => $v > 0)));
        if ($ids === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $params = array_merge($ids, [$associationId]);
        return $this->db->fetchAll(
            "SELECT id, member_number, name, mobile
             FROM members
             WHERE id IN ({$placeholders}) AND association_id = ? AND is_active = 1
             ORDER BY name ASC",
            $params
        );
    }
}
