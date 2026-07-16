<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class ProjectMilestone extends Model
{
    protected string $table = 'project_milestones';

    protected array $fillable = [
        'project_id', 'title', 'description', 'photo_path', 'achieved_on',
    ];

    /** @return list<array<string,mixed>> */
    public function forProject(int $projectId): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM project_milestones WHERE project_id = ? ORDER BY achieved_on DESC, id DESC',
            [$projectId]
        );
    }
}
