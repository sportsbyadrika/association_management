<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
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
        ];

        // Outstanding member dues, split by demand-purpose type
        // (mandatory vs optional). Sums the per-demand shortfall of every
        // pending/partial demand.
        $split = $db->fetch(
            "SELECT
                COALESCE(SUM(CASE WHEN dp.type = 'mandatory' THEN t.shortfall ELSE 0 END), 0) AS mandatory,
                COALESCE(SUM(CASE WHEN dp.type = 'mandatory' THEN 0 ELSE t.shortfall END), 0) AS optional
             FROM (
                SELECT d.demand_purpose_id, GREATEST(d.amount - COALESCE(r.paid, 0), 0) AS shortfall
                FROM demands d
                LEFT JOIN (SELECT demand_id, SUM(amount) AS paid FROM receipts WHERE association_id = ? GROUP BY demand_id) r
                    ON r.demand_id = d.id
                WHERE d.association_id = ? AND d.status IN ('pending', 'partial')
             ) t
             LEFT JOIN demand_purposes dp ON dp.id = t.demand_purpose_id",
            [$assocId, $assocId]
        );
        $stats['outstanding_mandatory'] = (float) ($split['mandatory'] ?? 0);
        $stats['outstanding_optional'] = (float) ($split['optional'] ?? 0);
        $stats['outstanding'] = $stats['outstanding_mandatory'] + $stats['outstanding_optional'];

        $recentReceipts = (new Receipt())->paginateForAssociation($assocId, 1, 5)['data'];
        $projects = array_slice((new Project())->allWithType($assocId), 0, 5);

        $this->view('dashboard.index', [
            'title'          => 'Dashboard',
            'stats'          => $stats,
            'recentReceipts' => $recentReceipts,
            'projects'       => $projects,
        ]);
    }
}
