<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class User extends Model
{
    protected string $table = 'users';

    protected array $fillable = [
        'association_id', 'member_id', 'name', 'email', 'password_hash',
        'role', 'permissions', 'is_active', 'must_change_password',
    ];

    /** @return array<string,mixed>|null */
    public function findByEmail(string $email): ?array
    {
        return $this->db->fetch(
            'SELECT * FROM users WHERE email = ? LIMIT 1',
            [strtolower(trim($email))]
        );
    }

    public function emailExists(string $email, ?int $ignoreId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM users WHERE email = ?';
        $params = [strtolower(trim($email))];
        if ($ignoreId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $ignoreId;
        }
        return (int) $this->db->fetchColumn($sql, $params) > 0;
    }

    public function createUser(array $data, string $plainPassword): int
    {
        $data['email'] = strtolower(trim((string) $data['email']));
        $data['password_hash'] = password_hash($plainPassword, PASSWORD_DEFAULT);
        return $this->create($data);
    }

    public function updatePassword(int $id, string $plainPassword, bool $clearMustChange = true): void
    {
        $hash = password_hash($plainPassword, PASSWORD_DEFAULT);
        $this->db->run(
            'UPDATE users SET password_hash = ?, must_change_password = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?',
            [$hash, $clearMustChange ? 0 : 1, $id]
        );
    }

    public function touchLastLogin(int $id): void
    {
        $this->db->run(
            'UPDATE users SET last_login_at = CURRENT_TIMESTAMP WHERE id = ?',
            [$id]
        );
    }

    /** @return list<array<string,mixed>> */
    public function adminsForAssociation(int $associationId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM users WHERE association_id = ? AND role IN ('association_admin','association_staff') ORDER BY name ASC",
            [$associationId]
        );
    }

    /** @return list<array<string,mixed>> */
    public function associationAdmins(): array
    {
        return $this->db->fetchAll(
            "SELECT u.*, a.name AS association_name
             FROM users u
             LEFT JOIN associations a ON a.id = u.association_id
             WHERE u.role = 'association_admin'
             ORDER BY a.name ASC, u.name ASC"
        );
    }

    public function setActive(int $id, bool $active): void
    {
        $this->db->run('UPDATE users SET is_active = ? WHERE id = ?', [$active ? 1 : 0, $id]);
    }
}
