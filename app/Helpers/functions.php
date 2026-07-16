<?php

declare(strict_types=1);

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Session;

/**
 * Global template + convenience helpers.
 */

if (!function_exists('e')) {
    /** Escape output for safe HTML rendering. */
    function e(mixed $value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return Csrf::field();
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return Csrf::token();
    }
}

if (!function_exists('method_field')) {
    function method_field(string $method): string
    {
        return '<input type="hidden" name="_method" value="' . e(strtoupper($method)) . '">';
    }
}

if (!function_exists('old')) {
    function old(string $key, mixed $default = ''): string
    {
        return e(Session::old($key, $default));
    }
}

if (!function_exists('error_for')) {
    function error_for(string $field): ?string
    {
        $errors = Session::errors();
        return $errors[$field] ?? null;
    }
}

if (!function_exists('has_error')) {
    function has_error(string $field): bool
    {
        return isset(Session::errors()[$field]);
    }
}

if (!function_exists('auth')) {
    /** @return array<string,mixed>|null */
    function auth(): ?array
    {
        return Auth::user();
    }
}

if (!function_exists('config')) {
    function config(?string $key = null, mixed $default = null): mixed
    {
        static $config = null;
        if ($config === null) {
            $config = require dirname(__DIR__, 2) . '/config/config.php';
        }
        if ($key === null) {
            return $config;
        }
        $value = $config;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        return $value;
    }
}

if (!function_exists('url')) {
    /**
     * Root-relative URL. Host-agnostic so the app works behind any domain and
     * keeps every request same-origin (important for the CSP 'self' policy).
     */
    function url(string $path = ''): string
    {
        return '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return '/' . ltrim($path, '/');
    }
}

if (!function_exists('abs_url')) {
    /** Absolute URL using APP_URL — for emails and other off-site contexts. */
    function abs_url(string $path = ''): string
    {
        $base = rtrim((string) config('app.url'), '/');
        return $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('money')) {
    /** Format a decimal amount for display. */
    function money(mixed $amount): string
    {
        return number_format((float) $amount, 2);
    }
}

if (!function_exists('format_date')) {
    function format_date(?string $date, string $format = 'd M Y'): string
    {
        if ($date === null || $date === '' || $date === '0000-00-00') {
            return '-';
        }
        $ts = strtotime($date);
        return $ts ? date($format, $ts) : '-';
    }
}

if (!function_exists('old_selected')) {
    function old_selected(string $field, mixed $value): string
    {
        return (string) Session::old($field) === (string) $value ? 'selected' : '';
    }
}

if (!function_exists('role_label')) {
    function role_label(?string $role): string
    {
        return match ($role) {
            'super_admin'       => 'Super Admin',
            'association_admin' => 'Association Admin',
            'association_staff' => 'Staff',
            'member'            => 'Member',
            default             => (string) $role,
        };
    }
}

if (!function_exists('active_class')) {
    function active_class(string $prefix, string $active = 'bg-brand-700 text-white', string $inactive = 'text-brand-100 hover:bg-brand-600 hover:text-white'): string
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        return str_starts_with($path, $prefix) ? $active : $inactive;
    }
}
