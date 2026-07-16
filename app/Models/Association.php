<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Association extends Model
{
    protected string $table = 'associations';

    protected array $fillable = [
        'name', 'logo_path', 'contact_email', 'contact_phone', 'address',
        'subscription_start', 'subscription_end', 'is_active',
    ];

    /**
     * Whether the association's subscription currently permits login.
     */
    public function subscriptionActive(array $association): bool
    {
        if ((int) $association['is_active'] !== 1) {
            return false;
        }
        $end = $association['subscription_end'] ?? null;
        if ($end === null || $end === '') {
            return true; // no expiry set
        }
        return strtotime($end) >= strtotime(date('Y-m-d'));
    }

    public function updateSubscription(int $id, ?string $start, ?string $end, bool $active): void
    {
        $this->db->run(
            'UPDATE associations SET subscription_start = ?, subscription_end = ?, is_active = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?',
            [$start ?: null, $end ?: null, $active ? 1 : 0, $id]
        );
    }
}
