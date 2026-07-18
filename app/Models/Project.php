<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Project extends Model
{
    protected string $table = 'projects';

    protected array $fillable = [
        'association_id', 'project_type_id', 'name', 'description', 'status',
        'target_amount', 'start_date', 'end_date',
    ];

    /** @return list<array<string,mixed>> */
    public function allWithType(int $associationId): array
    {
        return $this->db->fetchAll(
            "SELECT p.*, pt.name AS project_type_name,
                    (SELECT COALESCE(SUM(r.amount),0) FROM receipts r WHERE r.project_id = p.id) AS collected
             FROM projects p
             LEFT JOIN project_types pt ON pt.id = p.project_type_id
             WHERE p.association_id = ?
             ORDER BY p.created_at DESC",
            [$associationId]
        );
    }

    /** @return array<string,mixed>|null */
    public function findWithType(int $id, int $associationId): ?array
    {
        return $this->db->fetch(
            "SELECT p.*, pt.name AS project_type_name
             FROM projects p
             LEFT JOIN project_types pt ON pt.id = p.project_type_id
             WHERE p.id = ? AND p.association_id = ?",
            [$id, $associationId]
        );
    }

    public function collected(int $projectId): float
    {
        return (float) $this->db->fetchColumn(
            'SELECT COALESCE(SUM(amount),0) FROM receipts WHERE project_id = ?',
            [$projectId]
        );
    }

    public function spent(int $projectId): float
    {
        return (float) $this->db->fetchColumn(
            'SELECT COALESCE(SUM(amount),0) FROM expenditures WHERE project_id = ?',
            [$projectId]
        );
    }

    /** @return list<array<string,mixed>> */
    public function options(int $associationId): array
    {
        return $this->db->fetchAll(
            'SELECT id, name FROM projects WHERE association_id = ? ORDER BY name ASC',
            [$associationId]
        );
    }

    /**
     * Project-wise income (receipts) and expenditure within an optional date
     * range, plus a "general / non-project" bucket for entries not tied to a
     * project.
     *
     * @return array{rows:list<array<string,mixed>>,general:array{income:float,expense:float}}
     */
    public function incomeExpenditureByProject(int $associationId, ?string $from, ?string $to): array
    {
        $rWhere = '';
        $rDates = [];
        $eWhere = '';
        $eDates = [];
        if ($from !== null && $from !== '') {
            $rWhere .= ' AND received_on >= ?';
            $rDates[] = $from;
            $eWhere .= ' AND paid_on >= ?';
            $eDates[] = $from;
        }
        if ($to !== null && $to !== '') {
            $rWhere .= ' AND received_on <= ?';
            $rDates[] = $to;
            $eWhere .= ' AND paid_on <= ?';
            $eDates[] = $to;
        }

        $rows = $this->db->fetchAll(
            "SELECT p.id, p.name,
                    COALESCE(inc.total, 0) AS income,
                    COALESCE(exp.total, 0) AS expense
             FROM projects p
             LEFT JOIN (
                 SELECT project_id, SUM(amount) AS total FROM receipts
                 WHERE association_id = ?{$rWhere} AND project_id IS NOT NULL GROUP BY project_id
             ) inc ON inc.project_id = p.id
             LEFT JOIN (
                 SELECT project_id, SUM(amount) AS total FROM expenditures
                 WHERE association_id = ?{$eWhere} AND project_id IS NOT NULL GROUP BY project_id
             ) exp ON exp.project_id = p.id
             WHERE p.association_id = ?
             ORDER BY p.name ASC",
            array_merge([$associationId], $rDates, [$associationId], $eDates, [$associationId])
        );

        $genIncome = (float) $this->db->fetchColumn(
            "SELECT COALESCE(SUM(amount), 0) FROM receipts WHERE association_id = ? AND project_id IS NULL{$rWhere}",
            array_merge([$associationId], $rDates)
        );
        $genExpense = (float) $this->db->fetchColumn(
            "SELECT COALESCE(SUM(amount), 0) FROM expenditures WHERE association_id = ? AND project_id IS NULL{$eWhere}",
            array_merge([$associationId], $eDates)
        );

        return ['rows' => $rows, 'general' => ['income' => $genIncome, 'expense' => $genExpense]];
    }

    /**
     * Demands raised for a project with each member's payment status and the
     * last received date.
     * @return list<array<string,mixed>>
     */
    public function demandLedger(int $projectId, int $associationId): array
    {
        return $this->db->fetchAll(
            "SELECT d.id, d.amount, d.status, d.due_date,
                    m.member_number, m.name,
                    COALESCE(rr.paid, 0) AS paid, rr.last_received
             FROM demands d
             JOIN members m ON m.id = d.member_id
             LEFT JOIN (
                 SELECT demand_id, SUM(amount) AS paid, MAX(received_on) AS last_received
                 FROM receipts WHERE association_id = ? GROUP BY demand_id
             ) rr ON rr.demand_id = d.id
             WHERE d.association_id = ? AND d.project_id = ? AND d.status <> 'cancelled'
             ORDER BY m.name ASC",
            [$associationId, $associationId, $projectId]
        );
    }

    /**
     * Income booked to the project that is NOT a member demand collection
     * (receipts with no linked demand) — e.g. donations, grants, interest.
     * @return list<array<string,mixed>>
     */
    public function otherIncome(int $projectId, int $associationId): array
    {
        return $this->db->fetchAll(
            "SELECT r.received_on, r.amount, r.mode, r.remarks,
                    ih.name AS income_head_name, m.name AS member_name
             FROM receipts r
             LEFT JOIN income_heads ih ON ih.id = r.income_head_id
             LEFT JOIN members m ON m.id = r.member_id
             WHERE r.association_id = ? AND r.project_id = ? AND r.demand_id IS NULL
             ORDER BY r.received_on DESC, r.id DESC",
            [$associationId, $projectId]
        );
    }

    public function otherIncomeTotal(int $projectId, int $associationId): float
    {
        return (float) $this->db->fetchColumn(
            "SELECT COALESCE(SUM(amount),0) FROM receipts
             WHERE association_id = ? AND project_id = ? AND demand_id IS NULL",
            [$associationId, $projectId]
        );
    }

    /**
     * Expenditures booked to the project.
     * @return list<array<string,mixed>>
     */
    public function expenditureList(int $projectId, int $associationId): array
    {
        return $this->db->fetchAll(
            "SELECT e.paid_on, e.amount, e.mode, e.remarks, e.category,
                    eh.name AS head_name
             FROM expenditures e
             LEFT JOIN expenditure_heads eh ON eh.id = e.expenditure_head_id
             WHERE e.association_id = ? AND e.project_id = ?
             ORDER BY e.paid_on DESC, e.id DESC",
            [$associationId, $projectId]
        );
    }
}
