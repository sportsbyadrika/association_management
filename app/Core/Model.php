<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Base model. Provides generic CRUD helpers built on prepared statements.
 * Subclasses set $table and (optionally) $fillable + $associationScoped.
 */
abstract class Model
{
    protected string $table = '';

    /** Columns that may be mass-assigned via create()/update(). */
    protected array $fillable = [];

    /** When true, list/find helpers auto-filter by association_id. */
    protected bool $associationScoped = false;

    protected Database $db;

    public function __construct()
    {
        $this->db = Database::instance();
    }

    public function db(): Database
    {
        return $this->db;
    }

    /** @return array<string,mixed>|null */
    public function find(int $id): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM {$this->table} WHERE id = ?",
            [$id]
        );
    }

    /**
     * Find scoped to an association (returns null if it belongs elsewhere).
     * @return array<string,mixed>|null
     */
    public function findForAssociation(int $id, int $associationId): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM {$this->table} WHERE id = ? AND association_id = ?",
            [$id, $associationId]
        );
    }

    /** @return list<array<string,mixed>> */
    public function all(string $orderBy = 'id DESC'): array
    {
        return $this->db->fetchAll("SELECT * FROM {$this->table} ORDER BY {$orderBy}");
    }

    /** @return list<array<string,mixed>> */
    public function allForAssociation(int $associationId, string $orderBy = 'id DESC'): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM {$this->table} WHERE association_id = ? ORDER BY {$orderBy}",
            [$associationId]
        );
    }

    /** @return list<array<string,mixed>> */
    public function activeForAssociation(int $associationId, string $orderBy = 'name ASC'): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM {$this->table} WHERE association_id = ? AND is_active = 1 ORDER BY {$orderBy}",
            [$associationId]
        );
    }

    /**
     * Insert a row from a data array (filtered to $fillable) and return its id.
     */
    public function create(array $data): int
    {
        $data = $this->onlyFillable($data);
        if ($data === []) {
            throw new \InvalidArgumentException('No fillable data provided.');
        }
        $columns = array_keys($data);
        $placeholders = array_map(static fn ($c) => ':' . $c, $columns);
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );
        return $this->db->insert($sql, $this->prefixKeys($data));
    }

    public function update(int $id, array $data): bool
    {
        $data = $this->onlyFillable($data);
        if ($data === []) {
            return false;
        }
        $assignments = array_map(static fn ($c) => "{$c} = :{$c}", array_keys($data));
        $sql = sprintf(
            'UPDATE %s SET %s WHERE id = :__id',
            $this->table,
            implode(', ', $assignments)
        );
        $bindings = $this->prefixKeys($data);
        $bindings[':__id'] = $id;
        $this->db->run($sql, $bindings);
        return true;
    }

    public function delete(int $id): bool
    {
        $this->db->run("DELETE FROM {$this->table} WHERE id = ?", [$id]);
        return true;
    }

    /**
     * Paginate results. Returns [rows, total, pages].
     * @return array{data:list<array<string,mixed>>,total:int,page:int,perPage:int,pages:int}
     */
    protected function paginateQuery(string $baseSql, string $countSql, array $bindings, int $page, int $perPage): array
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;

        $total = (int) $this->db->fetchColumn($countSql, $bindings);
        $rows = $this->db->fetchAll(
            $baseSql . " LIMIT {$perPage} OFFSET {$offset}",
            $bindings
        );

        return [
            'data'    => $rows,
            'total'   => $total,
            'page'    => $page,
            'perPage' => $perPage,
            'pages'   => (int) ceil($total / $perPage),
        ];
    }

    private function onlyFillable(array $data): array
    {
        if ($this->fillable === []) {
            return $data;
        }
        return array_intersect_key($data, array_flip($this->fillable));
    }

    private function prefixKeys(array $data): array
    {
        $out = [];
        foreach ($data as $k => $v) {
            $out[':' . $k] = $v;
        }
        return $out;
    }
}
