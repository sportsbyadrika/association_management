<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Minimal .env loader.
 *
 * Prefers vlucas/phpdotenv when it is installed (via Composer) but falls back
 * to a self-contained parser so the application can boot even before
 * `composer install` has been run. Values are exposed through Env::get().
 */
final class Env
{
    /** @var array<string,string> */
    private static array $vars = [];

    private static bool $loaded = false;

    public static function load(string $path): void
    {
        if (self::$loaded) {
            return;
        }
        self::$loaded = true;

        // Use phpdotenv if present for full feature parity in production.
        if (class_exists(\Dotenv\Dotenv::class) && is_file($path)) {
            $dotenv = \Dotenv\Dotenv::createImmutable(dirname($path));
            $dotenv->safeLoad();
            self::$vars = array_map('strval', $_ENV);
            return;
        }

        if (!is_file($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (!str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Strip trailing inline comments for unquoted values.
            if ($value !== '' && $value[0] !== '"' && $value[0] !== "'") {
                $hashPos = strpos($value, ' #');
                if ($hashPos !== false) {
                    $value = rtrim(substr($value, 0, $hashPos));
                }
            }

            // Strip surrounding quotes.
            if (strlen($value) >= 2) {
                $first = $value[0];
                $last = $value[strlen($value) - 1];
                if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                    $value = substr($value, 1, -1);
                }
            }

            self::$vars[$key] = $value;
            $_ENV[$key] = $value;
            putenv($key . '=' . $value);
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, self::$vars)) {
            return self::$vars[$key];
        }
        $env = getenv($key);
        if ($env !== false) {
            return $env;
        }
        return $default;
    }
}
