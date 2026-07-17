<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Core\Database;

/**
 * Seeds sensible default master data for a newly-created association so the
 * app is immediately usable. All entries remain editable by the admin.
 */
final class Defaults
{
    public static function seedMasters(Database $db, int $associationId): void
    {
        $sets = [
            'member_types'      => ['Regular', 'Life', 'Honorary'],
            'income_heads'      => ['Subscription', 'Donation', 'Project Contribution'],
            'expenditure_heads' => ['Administrative', 'Project', 'Maintenance'],
            'project_types'     => ['General', 'Infrastructure', 'Welfare'],
        ];

        foreach ($sets as $table => $names) {
            foreach ($names as $name) {
                $db->run(
                    "INSERT INTO {$table} (association_id, name, is_active) VALUES (?, ?, 1)",
                    [$associationId, $name]
                );
            }
        }

        // A default association bank account.
        $db->run(
            'INSERT INTO bank_accounts (association_id, account_name, type, opening_balance, is_active)
             VALUES (?, ?, ?, ?, 1)',
            [$associationId, 'General Fund', 'association', 0.00]
        );

        // Default demand purposes with their mandatory/optional type.
        $purposes = [
            ['Subscription', 'mandatory'],
            ['Project Contribution', 'optional'],
            ['Donation', 'optional'],
            ['Other', 'optional'],
        ];
        foreach ($purposes as [$name, $type]) {
            $db->run(
                'INSERT INTO demand_purposes (association_id, name, type, is_active) VALUES (?, ?, ?, 1)',
                [$associationId, $name, $type]
            );
        }
    }
}
