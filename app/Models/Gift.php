<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Gift extends Model
{
    protected string $table = 'gifts';

    protected array $fillable = [
        'association_id', 'gift_type_id', 'direction', 'title', 'party',
        'member_id', 'value', 'gift_date', 'description', 'created_by',
    ];

    /**
     * @param string $direction '' = all, or 'in' / 'out'
     * @return array{data:list<array<string,mixed>>,total:int,page:int,perPage:int,pages:int}
     */
    public function paginateForAssociation(int $associationId, int $page = 1, int $perPage = 20, string $direction = '', string $search = ''): array
    {
        $where = 'WHERE g.association_id = ?';
        $params = [$associationId];
        if ($direction === 'in' || $direction === 'out') {
            $where .= ' AND g.direction = ?';
            $params[] = $direction;
        }
        if ($search !== '') {
            $where .= ' AND (g.title LIKE ? OR g.party LIKE ? OR m.name LIKE ?)';
            $like = '%' . $search . '%';
            array_push($params, $like, $like, $like);
        }

        $base = "SELECT g.*, gt.name AS gift_type_name, m.name AS member_name
                 FROM gifts g
                 LEFT JOIN gift_types gt ON gt.id = g.gift_type_id
                 LEFT JOIN members m ON m.id = g.member_id
                 {$where}
                 ORDER BY g.gift_date DESC, g.id DESC";
        $count = "SELECT COUNT(*) FROM gifts g LEFT JOIN members m ON m.id = g.member_id {$where}";
        return $this->paginateQuery($base, $count, $params, $page, $perPage);
    }

    /** @return array<string,mixed>|null */
    public function findWithRelations(int $id, int $associationId): ?array
    {
        return $this->db->fetch(
            "SELECT g.*, gt.name AS gift_type_name, m.name AS member_name
             FROM gifts g
             LEFT JOIN gift_types gt ON gt.id = g.gift_type_id
             LEFT JOIN members m ON m.id = g.member_id
             WHERE g.id = ? AND g.association_id = ?",
            [$id, $associationId]
        );
    }

    /** @return array<string,mixed>|null */
    public function findForAssociation(int $id, int $associationId): ?array
    {
        return $this->db->fetch(
            'SELECT * FROM gifts WHERE id = ? AND association_id = ?',
            [$id, $associationId]
        );
    }

    /**
     * Totals of gift value by direction.
     * @return array{in:float,out:float}
     */
    public function totals(int $associationId): array
    {
        $row = $this->db->fetch(
            "SELECT
                COALESCE(SUM(CASE WHEN direction = 'in' THEN value ELSE 0 END), 0) AS gin,
                COALESCE(SUM(CASE WHEN direction = 'out' THEN value ELSE 0 END), 0) AS gout
             FROM gifts WHERE association_id = ?",
            [$associationId]
        );
        return ['in' => (float) ($row['gin'] ?? 0), 'out' => (float) ($row['gout'] ?? 0)];
    }
}
