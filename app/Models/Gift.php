<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Gift extends Model
{
    protected string $table = 'gifts';

    protected array $fillable = [
        'association_id', 'gift_type_id', 'direction', 'title', 'party',
        'member_id', 'value', 'default_contribution', 'gift_date', 'description', 'created_by',
    ];

    /**
     * Members linked to a gift with their contribution amount and name.
     * @return list<array<string,mixed>>
     */
    public function members(int $giftId): array
    {
        return $this->db->fetchAll(
            "SELECT gm.member_id, gm.contribution, m.name, m.member_number
             FROM gift_members gm
             JOIN members m ON m.id = gm.member_id
             WHERE gm.gift_id = ?
             ORDER BY m.name ASC",
            [$giftId]
        );
    }

    public function totalContributions(int $giftId): float
    {
        return (float) $this->db->fetchColumn(
            'SELECT COALESCE(SUM(contribution),0) FROM gift_members WHERE gift_id = ?',
            [$giftId]
        );
    }

    /**
     * Replace a gift's member contributions. $pairs is [[member_id, amount], ...];
     * only members belonging to the association are stored.
     * @param list<array{0:int,1:float}> $pairs
     */
    public function syncMembers(int $giftId, int $associationId, array $pairs): void
    {
        $this->db->run('DELETE FROM gift_members WHERE gift_id = ?', [$giftId]);
        $seen = [];
        foreach ($pairs as [$memberId, $amount]) {
            $memberId = (int) $memberId;
            if ($memberId <= 0 || isset($seen[$memberId])) {
                continue;
            }
            $ok = (int) $this->db->fetchColumn(
                'SELECT COUNT(*) FROM members WHERE id = ? AND association_id = ?',
                [$memberId, $associationId]
            );
            if ($ok === 0) {
                continue;
            }
            $seen[$memberId] = true;
            $this->db->run(
                'INSERT INTO gift_members (association_id, gift_id, member_id, contribution) VALUES (?, ?, ?, ?)',
                [$associationId, $giftId, $memberId, round((float) $amount, 2)]
            );
        }
    }

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

    /** For select dropdowns. @return list<array<string,mixed>> */
    public function options(int $associationId): array
    {
        return $this->db->fetchAll(
            'SELECT id, title FROM gifts WHERE association_id = ? ORDER BY gift_date DESC, title ASC',
            [$associationId]
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
