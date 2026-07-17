<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class DemandPurpose extends Model
{
    protected string $table = 'demand_purposes';

    protected array $fillable = ['association_id', 'name', 'type', 'is_active'];

    /** @return list<array<string,mixed>> */
    public function allForAssociationOrdered(int $associationId): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM demand_purposes WHERE association_id = ? ORDER BY type ASC, name ASC',
            [$associationId]
        );
    }

    /** Active purposes for dropdowns. @return list<array<string,mixed>> */
    public function options(int $associationId): array
    {
        return $this->db->fetchAll(
            'SELECT id, name, type FROM demand_purposes WHERE association_id = ? AND is_active = 1 ORDER BY type ASC, name ASC',
            [$associationId]
        );
    }

    public function toggleActive(int $id, int $associationId): void
    {
        $this->db->run(
            'UPDATE demand_purposes SET is_active = 1 - is_active WHERE id = ? AND association_id = ?',
            [$id, $associationId]
        );
    }
}
