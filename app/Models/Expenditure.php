<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Expenditure extends Model
{
    protected string $table = 'expenditures';

    protected array $fillable = [
        'association_id', 'expenditure_head_id', 'project_id', 'category',
        'amount', 'paid_on', 'bank_account_id', 'mode', 'remarks', 'created_by',
    ];

    /**
     * @param int|string $projectFilter '' = all, 'none' = general (no project), or a project id
     * @return array{data:list<array<string,mixed>>,total:int,page:int,perPage:int,pages:int}
     */
    public function paginateForAssociation(int $associationId, int $page = 1, int $perPage = 20, int|string $projectFilter = '', ?string $from = null, ?string $to = null): array
    {
        $where = 'WHERE e.association_id = ?';
        $params = [$associationId];
        if ($projectFilter === 'none') {
            $where .= ' AND e.project_id IS NULL';
        } elseif ($projectFilter !== '' && (int) $projectFilter > 0) {
            $where .= ' AND e.project_id = ?';
            $params[] = (int) $projectFilter;
        }
        if ($from !== null && $from !== '') {
            $where .= ' AND e.paid_on >= ?';
            $params[] = $from;
        }
        if ($to !== null && $to !== '') {
            $where .= ' AND e.paid_on <= ?';
            $params[] = $to;
        }

        $base = "SELECT e.*, eh.name AS head_name, p.name AS project_name, b.account_name AS bank_name
                 FROM expenditures e
                 LEFT JOIN expenditure_heads eh ON eh.id = e.expenditure_head_id
                 LEFT JOIN projects p ON p.id = e.project_id
                 LEFT JOIN bank_accounts b ON b.id = e.bank_account_id
                 {$where}
                 ORDER BY e.paid_on DESC, e.id DESC";
        $count = "SELECT COUNT(*) FROM expenditures e {$where}";
        return $this->paginateQuery($base, $count, $params, $page, $perPage);
    }

    /**
     * Expenditure grouped by category (project vs association) and by head/project.
     * @return array{by_category:list<array<string,mixed>>,by_project:list<array<string,mixed>>,by_head:list<array<string,mixed>>,total:float}
     */
    public function expenditureReport(int $associationId, ?string $from, ?string $to): array
    {
        [$dateWhere, $params] = $this->dateFilter($from, $to);
        $params = array_merge([$associationId], $params);

        $byCategory = $this->db->fetchAll(
            "SELECT e.category, COUNT(*) AS count, COALESCE(SUM(e.amount),0) AS total
             FROM expenditures e WHERE e.association_id = ? {$dateWhere}
             GROUP BY e.category ORDER BY total DESC",
            $params
        );
        $byProject = $this->db->fetchAll(
            "SELECT COALESCE(p.name, 'Association (general)') AS project, COUNT(*) AS count, COALESCE(SUM(e.amount),0) AS total
             FROM expenditures e
             LEFT JOIN projects p ON p.id = e.project_id
             WHERE e.association_id = ? {$dateWhere}
             GROUP BY e.project_id, p.name ORDER BY total DESC",
            $params
        );
        $byHead = $this->db->fetchAll(
            "SELECT COALESCE(eh.name, 'Unspecified') AS head, COUNT(*) AS count, COALESCE(SUM(e.amount),0) AS total
             FROM expenditures e
             LEFT JOIN expenditure_heads eh ON eh.id = e.expenditure_head_id
             WHERE e.association_id = ? {$dateWhere}
             GROUP BY e.expenditure_head_id, eh.name ORDER BY total DESC",
            $params
        );
        $total = (float) $this->db->fetchColumn(
            "SELECT COALESCE(SUM(amount),0) FROM expenditures e WHERE e.association_id = ? {$dateWhere}",
            $params
        );
        return ['by_category' => $byCategory, 'by_project' => $byProject, 'by_head' => $byHead, 'total' => $total];
    }

    /** @return list<array<string,mixed>> */
    public function detailReport(int $associationId, ?string $from, ?string $to): array
    {
        [$dateWhere, $params] = $this->dateFilter($from, $to);
        $params = array_merge([$associationId], $params);
        return $this->db->fetchAll(
            "SELECT e.paid_on, eh.name AS head_name, p.name AS project_name,
                    e.category, e.mode, e.amount, e.remarks
             FROM expenditures e
             LEFT JOIN expenditure_heads eh ON eh.id = e.expenditure_head_id
             LEFT JOIN projects p ON p.id = e.project_id
             WHERE e.association_id = ? {$dateWhere}
             ORDER BY e.paid_on ASC, e.id ASC",
            $params
        );
    }

    public function totalForAssociation(int $associationId): float
    {
        return (float) $this->db->fetchColumn(
            'SELECT COALESCE(SUM(amount),0) FROM expenditures WHERE association_id = ?',
            [$associationId]
        );
    }

    /** @return array{0:string,1:list<string>} */
    private function dateFilter(?string $from, ?string $to): array
    {
        $where = '';
        $params = [];
        if ($from) {
            $where .= ' AND e.paid_on >= ?';
            $params[] = $from;
        }
        if ($to) {
            $where .= ' AND e.paid_on <= ?';
            $params[] = $to;
        }
        return [$where, $params];
    }
}
