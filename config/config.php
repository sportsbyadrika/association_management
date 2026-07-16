<?php

declare(strict_types=1);

use App\Core\Env;

/**
 * Central configuration. Reads from the environment (loaded via App\Core\Env)
 * and returns a plain array. Never hard-code secrets here.
 */

$bool = static fn (string $key, bool $default): bool => filter_var(
    Env::get($key, $default ? 'true' : 'false'),
    FILTER_VALIDATE_BOOLEAN
);

$int = static fn (string $key, int $default): int => (int) Env::get($key, $default);

return [
    'app' => [
        'name'  => (string) Env::get('APP_NAME', 'Habitract'),
        'env'   => (string) Env::get('APP_ENV', 'production'),
        'debug' => $bool('APP_DEBUG', false),
        'url'   => rtrim((string) Env::get('APP_URL', 'http://localhost:8000'), '/'),
        'key'   => (string) Env::get('APP_KEY', ''),
    ],
    'db' => [
        'connection' => (string) Env::get('DB_CONNECTION', 'mysql'),
        'host'       => (string) Env::get('DB_HOST', '127.0.0.1'),
        'port'       => $int('DB_PORT', 3306),
        'database'   => (string) Env::get('DB_DATABASE', 'habitract'),
        'username'   => (string) Env::get('DB_USERNAME', 'root'),
        'password'   => (string) Env::get('DB_PASSWORD', ''),
        'sqlite_path' => (string) Env::get('DB_SQLITE_PATH', 'storage/database.sqlite'),
        'charset'    => 'utf8mb4',
    ],
    'session' => [
        'name'             => (string) Env::get('SESSION_NAME', 'habitract_session'),
        'idle_timeout'     => $int('SESSION_IDLE_TIMEOUT', 1800),
        'absolute_timeout' => $int('SESSION_ABSOLUTE_TIMEOUT', 28800),
    ],
    'mail' => [
        'mailer'       => (string) Env::get('MAIL_MAILER', 'smtp'),
        'host'         => (string) Env::get('MAIL_HOST', 'localhost'),
        'port'         => $int('MAIL_PORT', 587),
        'username'     => (string) Env::get('MAIL_USERNAME', ''),
        'password'     => (string) Env::get('MAIL_PASSWORD', ''),
        'encryption'   => (string) Env::get('MAIL_ENCRYPTION', 'tls'),
        'from_address' => (string) Env::get('MAIL_FROM_ADDRESS', 'no-reply@habitract.local'),
        'from_name'    => (string) Env::get('MAIL_FROM_NAME', 'Habitract'),
    ],
    'super_admin' => [
        'name'     => (string) Env::get('SUPER_ADMIN_NAME', 'Super Admin'),
        'email'    => (string) Env::get('SUPER_ADMIN_EMAIL', 'admin@habitract.local'),
        'password' => (string) Env::get('SUPER_ADMIN_PASSWORD', 'ChangeMe!123'),
    ],
    'security' => [
        'login_max_attempts' => $int('LOGIN_MAX_ATTEMPTS', 5),
        'login_decay_minutes' => $int('LOGIN_DECAY_MINUTES', 15),
        'reset_token_ttl'    => $int('RESET_TOKEN_TTL', 60),
    ],
];
