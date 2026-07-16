<?php

declare(strict_types=1);

namespace Database\Seeds;

use App\Core\Database;
use App\Helpers\Defaults;

/**
 * Seeds the super admin (from config) plus a small demo association with
 * masters, members, and self-service accounts for testing.
 */
final class DatabaseSeeder
{
    public function __construct(private Database $db)
    {
    }

    public function run(): void
    {
        $config = require dirname(__DIR__, 2) . '/config/config.php';
        $this->seedSuperAdmin($config['super_admin']);
        $this->seedDemo();
        fwrite(STDOUT, "Seeding complete." . PHP_EOL);
    }

    private function seedSuperAdmin(array $sa): void
    {
        $email = strtolower(trim($sa['email']));
        $exists = (int) $this->db->fetchColumn(
            'SELECT COUNT(*) FROM users WHERE email = ?',
            [$email]
        );
        if ($exists > 0) {
            fwrite(STDOUT, "Super admin already exists ({$email})." . PHP_EOL);
            return;
        }
        $this->db->run(
            'INSERT INTO users (association_id, name, email, password_hash, role, is_active, must_change_password)
             VALUES (NULL, ?, ?, ?, ?, 1, 1)',
            [
                $sa['name'],
                $email,
                password_hash($sa['password'], PASSWORD_DEFAULT),
                'super_admin',
            ]
        );
        fwrite(STDOUT, "Created super admin: {$email}" . PHP_EOL);
    }

    private function seedDemo(): void
    {
        $exists = (int) $this->db->fetchColumn(
            'SELECT COUNT(*) FROM associations WHERE name = ?',
            ['Green Valley Residents Association']
        );
        if ($exists > 0) {
            fwrite(STDOUT, "Demo association already exists." . PHP_EOL);
            return;
        }

        $this->db->transaction(function (Database $db): void {
            $assocId = $db->insert(
                'INSERT INTO associations (name, contact_email, contact_phone, address, subscription_start, subscription_end, is_active)
                 VALUES (?, ?, ?, ?, ?, ?, 1)',
                [
                    'Green Valley Residents Association',
                    'office@greenvalley.example',
                    '+91 90000 00000',
                    '12 Green Valley Road, Springfield',
                    date('Y-m-d'),
                    date('Y-m-d', strtotime('+1 year')),
                ]
            );

            Defaults::seedMasters($db, $assocId);

            // Association admin
            $db->run(
                'INSERT INTO users (association_id, name, email, password_hash, role, is_active, must_change_password)
                 VALUES (?, ?, ?, ?, ?, 1, 0)',
                [
                    $assocId,
                    'Priya Admin',
                    'admin@greenvalley.example',
                    password_hash('Password!123', PASSWORD_DEFAULT),
                    'association_admin',
                ]
            );

            // Staff
            $db->run(
                'INSERT INTO users (association_id, name, email, password_hash, role, is_active, must_change_password)
                 VALUES (?, ?, ?, ?, ?, 1, 0)',
                [
                    $assocId,
                    'Ravi Staff',
                    'staff@greenvalley.example',
                    password_hash('Password!123', PASSWORD_DEFAULT),
                    'association_staff',
                ]
            );

            // A member type id to attach
            $memberTypeId = (int) $db->fetchColumn(
                'SELECT id FROM member_types WHERE association_id = ? ORDER BY id ASC LIMIT 1',
                [$assocId]
            );

            $members = [
                ['Anil Kumar', 42, 'male', '9800000001', 'anil@example.com', 4],
                ['Sunita Rao', 38, 'female', '9800000002', 'sunita@example.com', 3],
                ['John Mathew', 55, 'male', '9800000003', 'john@example.com', 2],
            ];
            $firstMemberId = null;
            foreach ($members as $m) {
                $mid = $db->insert(
                    'INSERT INTO members (association_id, member_type_id, name, age, gender, mobile, whatsapp, email, family_members_count, joined_on, is_active)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)',
                    [$assocId, $memberTypeId ?: null, $m[0], $m[1], $m[2], $m[3], $m[3], $m[4], $m[5], date('Y-m-d')]
                );
                $firstMemberId ??= $mid;
            }

            // A member self-service account tied to the first member.
            if ($firstMemberId !== null) {
                $db->run(
                    'INSERT INTO users (association_id, member_id, name, email, password_hash, role, is_active, must_change_password)
                     VALUES (?, ?, ?, ?, ?, ?, 1, 0)',
                    [
                        $assocId,
                        $firstMemberId,
                        'Anil Kumar',
                        'member@greenvalley.example',
                        password_hash('Password!123', PASSWORD_DEFAULT),
                        'member',
                    ]
                );
            }
        });

        fwrite(STDOUT, "Created demo association + users + members." . PHP_EOL);
        fwrite(STDOUT, "  admin@greenvalley.example / Password!123 (association admin)" . PHP_EOL);
        fwrite(STDOUT, "  staff@greenvalley.example / Password!123 (staff)" . PHP_EOL);
        fwrite(STDOUT, "  member@greenvalley.example / Password!123 (member)" . PHP_EOL);
    }
}
