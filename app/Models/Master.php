<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Generic model for the simple association-scoped lookup tables
 * (member_types, income_heads, expenditure_heads, project_types).
 * The concrete table is chosen at construction based on a whitelisted key.
 */
final class Master extends Model
{
    protected array $fillable = ['association_id', 'name', 'description', 'is_active'];

    /** @var array<string,string> whitelist: url segment => table */
    public const TABLES = [
        'member-types'      => 'member_types',
        'income-heads'      => 'income_heads',
        'expenditure-heads' => 'expenditure_heads',
        'project-types'     => 'project_types',
    ];

    /** @var array<string,string> */
    public const LABELS = [
        'member-types'      => 'Member Type',
        'income-heads'      => 'Income Head',
        'expenditure-heads' => 'Expenditure Head',
        'project-types'     => 'Project Type',
    ];

    public function __construct(string $key)
    {
        parent::__construct();
        if (!isset(self::TABLES[$key])) {
            throw new \InvalidArgumentException('Unknown master: ' . $key);
        }
        $this->table = self::TABLES[$key];
    }

    public function toggleActive(int $id, int $associationId): void
    {
        $this->db->run(
            "UPDATE {$this->table} SET is_active = 1 - is_active WHERE id = ? AND association_id = ?",
            [$id, $associationId]
        );
    }

    /** Whether the given key is a valid master. */
    public static function isValidKey(string $key): bool
    {
        return isset(self::TABLES[$key]);
    }
}
