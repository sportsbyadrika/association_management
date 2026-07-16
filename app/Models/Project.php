<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Project extends Model
{
    protected string $table = 'projects';

    protected array $fillable = [
        'association_id', 'project_type_id', 'name', 'description', 'status',
        'target_amount', 'start_date', 'end_date',
    ];

    /** @return list<array<string,mixed>> */
    public function allWithType(int $associationId): array
    {
        return $this->db->fetchAll(
            "SELECT p.*, pt.name AS project_type_name,
                    (SELECT COALESCE(SUM(r.amount),0) FROM receipts r WHERE r.project_id = p.id) AS collected
             FROM projects p
             LEFT JOIN project_types pt ON pt.id = p.project_type_id
             WHERE p.association_id = ?
             ORDER BY p.created_at DESC",
            [$associationId]
        );
    }

    /** @return array<string,mixed>|null */
    public function findWithType(int $id, int $associationId): ?array
    {
        return $this->db->fetch(
            "SELECT p.*, pt.name AS project_type_name
             FROM projects p
             LEFT JOIN project_types pt ON pt.id = p.project_type_id
             WHERE p.id = ? AND p.association_id = ?",
            [$id, $associationId]
        );
    }

    public function collected(int $projectId): float
    {
        return (float) $this->db->fetchColumn(
            'SELECT COALESCE(SUM(amount),0) FROM receipts WHERE project_id = ?',
            [$projectId]
        );
    }

    public function spent(int $projectId): float
    {
        return (float) $this->db->fetchColumn(
            'SELECT COALESCE(SUM(amount),0) FROM expenditures WHERE project_id = ?',
            [$projectId]
        );
    }

    /** @return list<array<string,mixed>> */
    public function options(int $associationId): array
    {
        return $this->db->fetchAll(
            'SELECT id, name FROM projects WHERE association_id = ? ORDER BY name ASC',
            [$associationId]
        );
    }
}
