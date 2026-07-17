<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class FinancialYear extends Model
{
    protected string $table = 'financial_years';

    protected array $fillable = [
        'association_id', 'label', 'start_date', 'end_date', 'is_active',
    ];

    /** @return list<array<string,mixed>> */
    public function allForAssociationOrdered(int $associationId): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM financial_years WHERE association_id = ? ORDER BY start_date DESC',
            [$associationId]
        );
    }

    /** Active financial years for dropdowns. @return list<array<string,mixed>> */
    public function options(int $associationId): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM financial_years WHERE association_id = ? AND is_active = 1 ORDER BY start_date DESC',
            [$associationId]
        );
    }

    /**
     * The financial year that contains today (falls back to the most recent
     * active one). Returns null if none defined.
     * @return array<string,mixed>|null
     */
    public function current(int $associationId): ?array
    {
        $today = date('Y-m-d');
        $row = $this->db->fetch(
            'SELECT * FROM financial_years
             WHERE association_id = ? AND is_active = 1 AND start_date <= ? AND end_date >= ?
             ORDER BY start_date DESC LIMIT 1',
            [$associationId, $today, $today]
        );
        if ($row !== null) {
            return $row;
        }
        return $this->db->fetch(
            'SELECT * FROM financial_years WHERE association_id = ? AND is_active = 1 ORDER BY start_date DESC LIMIT 1',
            [$associationId]
        );
    }
}
