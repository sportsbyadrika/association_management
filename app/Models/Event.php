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
        'status', 'value', 'description', 'created_by',
    ];

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
