<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Per-session CSRF token. A single rotating token is stored in the session and
 * required on every state-changing request.
 */
final class Csrf
{
    private const KEY = '__csrf_token';

    public static function token(): string
    {
        if (empty($_SESSION[self::KEY])) {
            $_SESSION[self::KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::KEY];
    }

    public static function validate(?string $token): bool
    {
        $stored = $_SESSION[self::KEY] ?? null;
        if (!is_string($stored) || !is_string($token) || $token === '') {
            return false;
        }
        return hash_equals($stored, $token);
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_token" value="' . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8') . '">';
    }
}
