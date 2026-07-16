<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class LoginAttempt extends Model
{
    protected string $table = 'login_attempts';

    public function record(string $email, string $ip, bool $success): void
    {
        $this->db->run(
            'INSERT INTO login_attempts (email, ip, success) VALUES (?, ?, ?)',
            [strtolower(trim($email)), $ip, $success ? 1 : 0]
        );
    }

    /**
     * Count recent failed attempts for an email OR ip within the decay window.
     */
    public function recentFailures(string $email, string $ip, int $decayMinutes): int
    {
        $since = date('Y-m-d H:i:s', time() - $decayMinutes * 60);
        return (int) $this->db->fetchColumn(
            'SELECT COUNT(*) FROM login_attempts
             WHERE success = 0 AND attempted_at >= ? AND (email = ? OR ip = ?)',
            [$since, strtolower(trim($email)), $ip]
        );
    }

    public function clearFor(string $email, string $ip): void
    {
        $this->db->run(
            'DELETE FROM login_attempts WHERE success = 0 AND (email = ? OR ip = ?)',
            [strtolower(trim($email)), $ip]
        );
    }
}
