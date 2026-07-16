<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Session wrapper. Configures secure cookie flags, handles idle + absolute
 * timeouts, flash messages and CSRF token storage.
 */
final class Session
{
    private static bool $started = false;

    public static function start(array $config): void
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }

        $https = (($_SERVER['HTTPS'] ?? '') !== '' && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['SERVER_PORT'] ?? '') === '443');

        session_name($config['name'] ?? 'habitract_session');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $https,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
        self::$started = true;

        self::enforceTimeouts(
            (int) ($config['idle_timeout'] ?? 1800),
            (int) ($config['absolute_timeout'] ?? 28800)
        );
    }

    private static function enforceTimeouts(int $idle, int $absolute): void
    {
        $now = time();
        $started = $_SESSION['__started_at'] ?? null;
        $last = $_SESSION['__last_activity'] ?? null;

        if ($started !== null && ($now - (int) $started) > $absolute) {
            self::destroy();
            session_start();
        } elseif ($last !== null && ($now - (int) $last) > $idle) {
            self::destroy();
            session_start();
        }

        if (!isset($_SESSION['__started_at'])) {
            $_SESSION['__started_at'] = $now;
        }
        $_SESSION['__last_activity'] = $now;
    }

    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                [
                    'expires'  => time() - 42000,
                    'path'     => $params['path'],
                    'domain'   => $params['domain'],
                    'secure'   => $params['secure'],
                    'httponly' => $params['httponly'],
                    'samesite' => 'Lax',
                ]
            );
        }
        session_destroy();
    }

    // ---- Flash messages -------------------------------------------------

    public static function flash(string $type, string $message): void
    {
        $_SESSION['__flash'][] = ['type' => $type, 'message' => $message];
    }

    /** @return list<array{type:string,message:string}> */
    public static function pullFlash(): array
    {
        $flash = $_SESSION['__flash'] ?? [];
        unset($_SESSION['__flash']);
        return $flash;
    }

    // ---- Old input (for repopulating forms after validation) ------------

    public static function flashInput(array $input): void
    {
        // Never retain sensitive fields.
        unset($input['password'], $input['password_confirmation'], $input['current_password'], $input['_token']);
        $_SESSION['__old'] = $input;
    }

    public static function old(string $key, mixed $default = null): mixed
    {
        return $_SESSION['__old'][$key] ?? $default;
    }

    public static function flashErrors(array $errors): void
    {
        $_SESSION['__errors'] = $errors;
    }

    /** @return array<string,string> */
    public static function errors(): array
    {
        return $_SESSION['__errors'] ?? [];
    }

    public static function clearFormState(): void
    {
        unset($_SESSION['__old'], $_SESSION['__errors']);
    }
}
