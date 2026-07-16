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
            'outstanding'  => (float) $db->fetchColumn(
                "SELECT COALESCE(SUM(amount),0) FROM demands WHERE association_id = ? AND status <> 'cancelled'",
                [$assocId]
            ) - (float) $db->fetchColumn('SELECT COALESCE(SUM(amount),0) FROM receipts WHERE association_id = ?', [$assocId]),
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
