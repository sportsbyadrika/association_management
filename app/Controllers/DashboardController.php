<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Models\Demand;
use App\Models\Expenditure;
use App\Models\FinancialYear;
use App\Models\Member;
use App\Models\Project;
use App\Models\Receipt;

final class DashboardController extends Controller
{
    public function index(Request $request): void
    {
        $assocId = Auth::associationId();
        $this->authorizeAssociation($assocId);

        $db = (new Member())->db();
        [$financialYears, $selectedFy, $fyParam] = $this->resolveFinancialYear($request, $assocId);
        $from = $selectedFy['start_date'] ?? null;
        $to = $selectedFy['end_date'] ?? null;
        $inRange = $from !== null && $to !== null;

        // Receipts total, financial-year scoped.
        $recSql = 'SELECT COALESCE(SUM(amount),0) FROM receipts WHERE association_id = ?';
        $recParams = [$assocId];
        if ($inRange) {
            $recSql .= ' AND received_on BETWEEN ? AND ?';
            array_push($recParams, $from, $to);
        }
        // Expenditure total, financial-year scoped.
        $expSql = 'SELECT COALESCE(SUM(amount),0) FROM expenditures WHERE association_id = ?';
        $expParams = [$assocId];
        if ($inRange) {
            $expSql .= ' AND paid_on BETWEEN ? AND ?';
            array_push($expParams, $from, $to);
        }

        $stats = [
            'members'        => (new Member())->countForAssociation($assocId),
            'receipts'       => (float) $db->fetchColumn($recSql, $recParams),
            'expenditures'   => (float) $db->fetchColumn($expSql, $expParams),
            'projects'       => (int) $db->fetchColumn("SELECT COUNT(*) FROM projects WHERE association_id = ? AND status IN ('planned','active')", [$assocId]),
            'projects_total' => (int) $db->fetchColumn('SELECT COUNT(*) FROM projects WHERE association_id = ?', [$assocId]),
        ];

        // Active-member count broken down by member type (current totals).
        $memberTypeCounts = $db->fetchAll(
            "SELECT COALESCE(mt.name, 'Unspecified') AS type, COUNT(*) AS count
             FROM members m
             LEFT JOIN member_types mt ON mt.id = m.member_type_id
             WHERE m.association_id = ? AND m.is_active = 1
             GROUP BY m.member_type_id, mt.name
             ORDER BY count DESC, type ASC",
            [$assocId]
        );

        // Project type-wise: count, total target and collected (collected FY-scoped).
        $collectedSub = 'SELECT project_id, SUM(amount) AS collected FROM receipts WHERE association_id = ?';
        $ptParams = [$assocId];
        if ($inRange) {
            $collectedSub .= ' AND received_on BETWEEN ? AND ?';
            array_push($ptParams, $from, $to);
        }
        $collectedSub .= ' GROUP BY project_id';
        $ptParams[] = $assocId;
        $projectTypeSummary = $db->fetchAll(
            "SELECT COALESCE(pt.name, 'Unspecified') AS type,
                    COUNT(*) AS count,
                    COALESCE(SUM(p.target_amount), 0) AS target,
                    COALESCE(SUM(rc.collected), 0) AS collected
             FROM projects p
             LEFT JOIN project_types pt ON pt.id = p.project_type_id
             LEFT JOIN ({$collectedSub}) rc ON rc.project_id = p.id
             WHERE p.association_id = ?
             GROUP BY p.project_type_id, pt.name
             ORDER BY count DESC, type ASC",
            $ptParams
        );

        $recentReceipts = (new Receipt())->paginateForAssociation($assocId, 1, 5, '', '', $from, $to)['data'];

        $this->view('dashboard.index', [
            'title'              => 'Dashboard',
            'stats'              => $stats,
            'subscription'       => (new Demand())->subscriptionSummary($assocId, $from, $to),
            'memberTypeCounts'   => $memberTypeCounts,
            'projectTypeSummary' => $projectTypeSummary,
            'recentReceipts'     => $recentReceipts,
            'financialYears'     => $financialYears,
            'selectedFy'         => $selectedFy,
            'fyParam'            => $fyParam,
        ]);
    }

    /**
     * Subscription drill-down: total / received / outstanding lists on one page.
     */
    public function subscriptions(Request $request): void
    {
        $assocId = Auth::associationId();
        $this->authorizeAssociation($assocId);

        $view = (string) $request->input('view', 'total');
        if (!in_array($view, ['total', 'received', 'outstanding'], true)) {
            $view = 'total';
        }
        [$financialYears, $selectedFy, $fyParam] = $this->resolveFinancialYear($request, $assocId);
        $from = $selectedFy['start_date'] ?? null;
        $to = $selectedFy['end_date'] ?? null;

        $this->view('dashboard.subscriptions', [
            'title'          => 'Subscriptions',
            'view'           => $view,
            'summary'        => (new Demand())->subscriptionSummary($assocId, $from, $to),
            'rows'           => (new Demand())->subscriptionList($assocId, $view, $from, $to),
            'financialYears' => $financialYears,
            'selectedFy'     => $selectedFy,
            'fyParam'        => $fyParam,
        ]);
    }

    /**
     * Resolve the selected financial year (default: current). Mirrors the dues
     * list. Returns [financialYears, selectedFy|null, fyParam].
     *
     * @return array{0:list<array<string,mixed>>,1:array<string,mixed>|null,2:mixed}
     */
    private function resolveFinancialYear(Request $request, int $assocId): array
    {
        $fyModel = new FinancialYear();
        $financialYears = $fyModel->allForAssociationOrdered($assocId);
        $fyParam = $request->input('fy');
        $selectedFy = null;
        if ($fyParam === 'all') {
            $selectedFy = null;
        } elseif ($fyParam !== null && $fyParam !== '') {
            foreach ($financialYears as $fy) {
                if ((int) $fy['id'] === (int) $fyParam) {
                    $selectedFy = $fy;
                    break;
                }
            }
        } else {
            $selectedFy = $fyModel->current($assocId);
        }
        return [$financialYears, $selectedFy, $fyParam];
    }
}
