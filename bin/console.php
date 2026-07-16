<?php

declare(strict_types=1);

/**
 * Habitract CLI — migrations + seeding.
 *
 *   php bin/console.php migrate      Run pending migrations
 *   php bin/console.php seed         Run seeders (super admin + demo data)
 *   php bin/console.php fresh        Drop all tables, migrate, then seed
 *   php bin/console.php key:generate Print a random APP_KEY
 */

use App\Core\Database;
use App\Core\Env;

define('BASE_PATH', dirname(__DIR__));

// Autoload
$composer = BASE_PATH . '/vendor/autoload.php';
if (is_file($composer)) {
    require $composer;
} else {
    spl_autoload_register(static function (string $class): void {
        if (!str_starts_with($class, 'App\\')) {
            return;
        }
        $file = BASE_PATH . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (is_file($file)) {
            require $file;
        }
    });
    require BASE_PATH . '/app/Helpers/functions.php';
}

Env::load(BASE_PATH . '/.env');

$command = $argv[1] ?? 'help';

function out(string $msg): void
{
    fwrite(STDOUT, $msg . PHP_EOL);
}

function ensureMigrationsTable(Database $db): void
{
    $db->pdo()->exec(
        'CREATE TABLE IF NOT EXISTS migrations (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            filename VARCHAR(255) NOT NULL,
            applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_migration (filename)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
}

/** Split a .sql file into individual statements (handles ; delimiters). */
function splitSql(string $sql): array
{
    // Strip line comments beginning with -- .
    $lines = preg_split('/\r?\n/', $sql) ?: [];
    $clean = [];
    foreach ($lines as $line) {
        if (preg_match('/^\s*--/', $line)) {
            continue;
        }
        $clean[] = $line;
    }
    $sql = implode("\n", $clean);

    $statements = [];
    foreach (explode(';', $sql) as $stmt) {
        $stmt = trim($stmt);
        if ($stmt !== '') {
            $statements[] = $stmt;
        }
    }
    return $statements;
}

function runMigrations(Database $db): void
{
    ensureMigrationsTable($db);
    $applied = array_column(
        $db->fetchAll('SELECT filename FROM migrations'),
        'filename'
    );
    $files = glob(BASE_PATH . '/database/migrations/*.sql') ?: [];
    sort($files);

    $ran = 0;
    foreach ($files as $file) {
        $name = basename($file);
        if (in_array($name, $applied, true)) {
            continue;
        }
        out("  → applying {$name}");
        $sql = (string) file_get_contents($file);
        foreach (splitSql($sql) as $stmt) {
            $db->pdo()->exec($stmt);
        }
        $db->run('INSERT INTO migrations (filename) VALUES (?)', [$name]);
        $ran++;
    }
    out($ran === 0 ? 'Nothing to migrate.' : "Migrated {$ran} file(s).");
}

function dropAllTables(Database $db): void
{
    $pdo = $db->pdo();
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    $tables = $db->fetchAll('SHOW TABLES');
    foreach ($tables as $row) {
        $table = array_values($row)[0];
        $pdo->exec("DROP TABLE IF EXISTS `{$table}`");
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    out('Dropped all tables.');
}

try {
    $db = Database::instance();

    switch ($command) {
        case 'migrate':
            runMigrations($db);
            break;

        case 'seed':
            require BASE_PATH . '/database/seeds/DatabaseSeeder.php';
            (new \Database\Seeds\DatabaseSeeder($db))->run();
            break;

        case 'fresh':
            dropAllTables($db);
            runMigrations($db);
            require BASE_PATH . '/database/seeds/DatabaseSeeder.php';
            (new \Database\Seeds\DatabaseSeeder($db))->run();
            break;

        case 'key:generate':
            out(bin2hex(random_bytes(24)));
            break;

        default:
            out('Habitract console');
            out('Usage: php bin/console.php [migrate|seed|fresh|key:generate]');
    }
} catch (\Throwable $e) {
    fwrite(STDERR, 'Error: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
