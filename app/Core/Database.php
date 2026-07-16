<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;

/**
 * Thin PDO wrapper. Enforces prepared statements everywhere — callers pass
 * SQL with named/positional placeholders and a bindings array; there is no
 * API that concatenates values into SQL.
 */
final class Database
{
    private static ?self $instance = null;

    private PDO $pdo;

    private function __construct(array $config)
    {
        $connection = $config['connection'] ?? 'mysql';

        if ($connection === 'sqlite') {
            $path = $config['sqlite_path'];
            if (!str_starts_with($path, '/')) {
                $path = dirname(__DIR__, 2) . '/' . $path;
            }
            $dsn = 'sqlite:' . $path;
            $username = null;
            $password = null;
        } else {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $config['host'],
                $config['port'],
                $config['database'],
                $config['charset'] ?? 'utf8mb4'
            );
            $username = $config['username'];
            $password = $config['password'];
        }

        try {
            $this->pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            if ($connection === 'sqlite') {
                $this->pdo->exec('PRAGMA foreign_keys = ON');
            }
        } catch (PDOException $e) {
            // Never leak DSN/credentials to the caller.
            Logger::error('Database connection failed: ' . $e->getMessage());
            throw new RuntimeException('Database connection failed.');
        }
    }

    public static function instance(?array $config = null): self
    {
        if (self::$instance === null) {
            if ($config === null) {
                $config = (require dirname(__DIR__, 2) . '/config/config.php')['db'];
            }
            self::$instance = new self($config);
        }
        return self::$instance;
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * @param array<string,mixed>|list<mixed> $bindings
     */
    public function run(string $sql, array $bindings = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bindings);
        return $stmt;
    }

    /** @return array<string,mixed>|null */
    public function fetch(string $sql, array $bindings = []): ?array
    {
        $row = $this->run($sql, $bindings)->fetch();
        return $row === false ? null : $row;
    }

    /** @return list<array<string,mixed>> */
    public function fetchAll(string $sql, array $bindings = []): array
    {
        return $this->run($sql, $bindings)->fetchAll();
    }

    public function fetchColumn(string $sql, array $bindings = []): mixed
    {
        return $this->run($sql, $bindings)->fetchColumn();
    }

    public function insert(string $sql, array $bindings = []): int
    {
        $this->run($sql, $bindings);
        return (int) $this->pdo->lastInsertId();
    }

    public function beginTransaction(): void
    {
        if (!$this->pdo->inTransaction()) {
            $this->pdo->beginTransaction();
        }
    }

    public function commit(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->commit();
        }
    }

    public function rollBack(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    /**
     * Run a callback inside a transaction, rolling back on any exception.
     */
    public function transaction(callable $callback): mixed
    {
        $this->beginTransaction();
        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->rollBack();
            throw $e;
        }
    }
}
