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
            // Outstanding = non-cancelled demands − receipts − amounts settled
            // via "mark paid" (paid demands not covered by a receipt).
            'outstanding'  => (float) $db->fetchColumn(
                "SELECT COALESCE(SUM(amount),0) FROM demands WHERE association_id = ? AND status <> 'cancelled'",
                [$assocId]
            )
            - (float) $db->fetchColumn('SELECT COALESCE(SUM(amount),0) FROM receipts WHERE association_id = ?', [$assocId])
            - (float) $db->fetchColumn(
                "SELECT COALESCE(SUM(GREATEST(d.amount - COALESCE(r.paid,0), 0)),0)
                 FROM demands d
                 LEFT JOIN (SELECT demand_id, SUM(amount) AS paid FROM receipts WHERE association_id = ? GROUP BY demand_id) r
                    ON r.demand_id = d.id
                 WHERE d.association_id = ? AND d.status = 'paid'",
                [$assocId, $assocId]
            ),
        ];

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
