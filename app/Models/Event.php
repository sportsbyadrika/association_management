<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Event extends Model
{
    protected string $table = 'events';

    protected array $fillable = [
        'association_id', 'event_type_id', 'title', 'venue', 'location',
        'start_date', 'end_date', 'registration_start', 'registration_end',
        'status', 'value', 'default_contribution', 'description', 'created_by',
    ];

    /**
     * Members linked to an event with their contribution amount and name.
     * @return list<array<string,mixed>>
     */
    public function members(int $eventId): array
    {
        return $this->db->fetchAll(
            "SELECT em.member_id, em.contribution, m.name, m.member_number
             FROM event_members em
             JOIN members m ON m.id = em.member_id
             WHERE em.event_id = ?
             ORDER BY m.name ASC",
            [$eventId]
        );
    }

    public function totalContributions(int $eventId): float
    {
        return (float) $this->db->fetchColumn(
            'SELECT COALESCE(SUM(contribution),0) FROM event_members WHERE event_id = ?',
            [$eventId]
        );
    }

    /**
     * Replace an event's member contributions. $pairs is [[member_id, amount], ...];
     * only members belonging to the association are stored.
     * @param list<array{0:int,1:float}> $pairs
     */
    public function syncMembers(int $eventId, int $associationId, array $pairs): void
    {
        $this->db->run('DELETE FROM event_members WHERE event_id = ?', [$eventId]);
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
                'INSERT INTO event_members (association_id, event_id, member_id, contribution) VALUES (?, ?, ?, ?)',
                [$associationId, $eventId, $memberId, round((float) $amount, 2)]
            );
        }
    }

    /**
     * @return array{data:list<array<string,mixed>>,total:int,page:int,perPage:int,pages:int}
     */
    public function paginateForAssociation(int $associationId, int $page = 1, int $perPage = 20, string $search = ''): array
    {
        $where = 'WHERE e.association_id = ?';
        $params = [$associationId];
        if ($search !== '') {
            $where .= ' AND (e.title LIKE ? OR e.venue LIKE ?)';
            $like = '%' . $search . '%';
            array_push($params, $like, $like);
        }

        $base = "SELECT e.*, et.name AS event_type_name
                 FROM events e
                 LEFT JOIN event_types et ON et.id = e.event_type_id
                 {$where}
                 ORDER BY e.start_date DESC, e.id DESC";
        $count = "SELECT COUNT(*) FROM events e {$where}";
        return $this->paginateQuery($base, $count, $params, $page, $perPage);
    }

    /** @return array<string,mixed>|null */
    public function findWithType(int $id, int $associationId): ?array
    {
        return $this->db->fetch(
            "SELECT e.*, et.name AS event_type_name
             FROM events e
             LEFT JOIN event_types et ON et.id = e.event_type_id
             WHERE e.id = ? AND e.association_id = ?",
            [$id, $associationId]
        );
    }

    /** @return array<string,mixed>|null */
    public function findForAssociation(int $id, int $associationId): ?array
    {
        return $this->db->fetch(
            'SELECT * FROM events WHERE id = ? AND association_id = ?',
            [$id, $associationId]
        );
    }

    /** For select dropdowns. @return list<array<string,mixed>> */
    public function options(int $associationId): array
    {
        return $this->db->fetchAll(
            'SELECT id, title FROM events WHERE association_id = ? ORDER BY start_date DESC, title ASC',
            [$associationId]
        );
    }

    public function spent(int $eventId): float
    {
        return (float) $this->db->fetchColumn(
            'SELECT COALESCE(SUM(amount),0) FROM expenditures WHERE event_id = ?',
            [$eventId]
        );
    }

    public function collected(int $eventId): float
    {
        return (float) $this->db->fetchColumn(
            'SELECT COALESCE(SUM(amount),0) FROM receipts WHERE event_id = ?',
            [$eventId]
        );
    }
}
