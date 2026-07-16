<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\User;

/**
 * Authentication + current-user resolution. Only the user id + role are held
 * in the session; the full user record is reloaded from the database on each
 * request so revocations/role changes take effect immediately.
 */
final class Auth
{
    private static ?array $user = null;
    private static bool $resolved = false;

    public static function attempt(string $email, string $password): ?array
    {
        $user = (new User())->findByEmail($email);
        if ($user === null || (int) $user['is_active'] !== 1) {
            return null;
        }
        if (!password_verify($password, $user['password_hash'])) {
            return null;
        }
        // Transparently upgrade the hash if the default algorithm changed.
        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            (new User())->updatePassword((int) $user['id'], $password);
        }
        return $user;
    }

    public static function login(array $user): void
    {
        Session::regenerate();
        Session::set('__auth_id', (int) $user['id']);
        Session::set('__auth_role', $user['role']);
        self::$user = null;
        self::$resolved = false;
        (new User())->touchLastLogin((int) $user['id']);
    }

    public static function logout(): void
    {
        Session::forget('__auth_id');
        Session::forget('__auth_role');
        self::$user = null;
        self::$resolved = false;
        Session::destroy();
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    /** @return array<string,mixed>|null */
    public static function user(): ?array
    {
        if (self::$resolved) {
            return self::$user;
        }
        self::$resolved = true;

        $id = Session::get('__auth_id');
        if ($id === null) {
            return self::$user = null;
        }

        $user = (new User())->find((int) $id);
        if ($user === null || (int) $user['is_active'] !== 1) {
            self::logout();
            return self::$user = null;
        }
        return self::$user = $user;
    }

    public static function id(): ?int
    {
        $user = self::user();
        return $user ? (int) $user['id'] : null;
    }

    public static function role(): ?string
    {
        $user = self::user();
        return $user['role'] ?? null;
    }

    public static function associationId(): ?int
    {
        $user = self::user();
        if ($user === null || $user['association_id'] === null) {
            return null;
        }
        return (int) $user['association_id'];
    }

    public static function is(string ...$roles): bool
    {
        return in_array(self::role(), $roles, true);
    }

    public static function isSuperAdmin(): bool
    {
        return self::role() === 'super_admin';
    }

    public static function mustChangePassword(): bool
    {
        $user = self::user();
        return $user !== null && (int) ($user['must_change_password'] ?? 0) === 1;
    }
}
