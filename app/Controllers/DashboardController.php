<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Models\Demand;
use App\Models\Expenditure;
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

        $stats = [
            'members'      => (new Member())->countForAssociation($assocId),
            'receipts'     => (float) $db->fetchColumn('SELECT COALESCE(SUM(amount),0) FROM receipts WHERE association_id = ?', [$assocId]),
            'expenditures' => (new Expenditure())->totalForAssociation($assocId),
            'projects'     => (int) $db->fetchColumn("SELECT COUNT(*) FROM projects WHERE association_id = ? AND status IN ('planned','active')", [$assocId]),
            'projects_total' => (int) $db->fetchColumn('SELECT COUNT(*) FROM projects WHERE association_id = ?', [$assocId]),
        ];

        // Active-member count broken down by member type.
        $memberTypeCounts = $db->fetchAll(
            "SELECT COALESCE(mt.name, 'Unspecified') AS type, COUNT(*) AS count
             FROM members m
             LEFT JOIN member_types mt ON mt.id = m.member_type_id
             WHERE m.association_id = ? AND m.is_active = 1
             GROUP BY m.member_type_id, mt.name
             ORDER BY count DESC, type ASC",
            [$assocId]
        );

        // Project type-wise: count, total target and total collected.
        $projectTypeSummary = $db->fetchAll(
            "SELECT COALESCE(pt.name, 'Unspecified') AS type,
                    COUNT(*) AS count,
                    COALESCE(SUM(p.target_amount), 0) AS target,
                    COALESCE(SUM(rc.collected), 0) AS collected
             FROM projects p
             LEFT JOIN project_types pt ON pt.id = p.project_type_id
             LEFT JOIN (SELECT project_id, SUM(amount) AS collected FROM receipts WHERE association_id = ? GROUP BY project_id) rc
                 ON rc.project_id = p.id
             WHERE p.association_id = ?
             GROUP BY p.project_type_id, pt.name
             ORDER BY count DESC, type ASC",
            [$assocId, $assocId]
        );

        $recentReceipts = (new Receipt())->paginateForAssociation($assocId, 1, 5)['data'];

        $this->view('dashboard.index', [
            'title'              => 'Dashboard',
            'stats'              => $stats,
            'subscription'       => (new Demand())->subscriptionSummary($assocId),
            'memberTypeCounts'   => $memberTypeCounts,
            'projectTypeSummary' => $projectTypeSummary,
            'recentReceipts'     => $recentReceipts,
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

        $this->view('dashboard.subscriptions', [
            'title'   => 'Subscriptions',
            'view'    => $view,
            'summary' => (new Demand())->subscriptionSummary($assocId),
            'rows'    => (new Demand())->subscriptionList($assocId, $view),
        ]);
    }
}
