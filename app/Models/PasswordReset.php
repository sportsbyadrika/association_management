<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class PasswordReset extends Model
{
    protected string $table = 'password_resets';

    /**
     * Create a single-use reset token. Returns the *plaintext* token to email;
     * only its hash is stored.
     */
    public function issue(int $userId, int $ttlMinutes): string
    {
        // Invalidate any outstanding tokens for this user.
        $this->db->run('DELETE FROM password_resets WHERE user_id = ?', [$userId]);

        $token = bin2hex(random_bytes(32));
        $hash = hash('sha256', $token);
        $expires = date('Y-m-d H:i:s', time() + $ttlMinutes * 60);

        $this->db->run(
            'INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, ?)',
            [$userId, $hash, $expires]
        );
        return $token;
    }

    /** @return array<string,mixed>|null */
    public function findValid(string $token): ?array
    {
        $hash = hash('sha256', $token);
        return $this->db->fetch(
            'SELECT * FROM password_resets
             WHERE token_hash = ? AND used_at IS NULL AND expires_at >= CURRENT_TIMESTAMP
             LIMIT 1',
            [$hash]
        );
    }

    public function markUsed(int $id): void
    {
        $this->db->run(
            'UPDATE password_resets SET used_at = CURRENT_TIMESTAMP WHERE id = ?',
            [$id]
        );
    }
}
