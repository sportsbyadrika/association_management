<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Models\Association;
use App\Models\Demand;
use App\Models\Expenditure;
use App\Models\FinancialYear;
use App\Models\Member;
use App\Models\Project;
use App\Models\Receipt;
use App\Services\CsvExporter;
use App\Services\ImageUploader;
use App\Services\PdfReport;

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

        $summary = (new Demand())->subscriptionSummary($assocId, $from, $to);
        $rows = (new Demand())->subscriptionList($assocId, $view, $from, $to);

        $labels = ['total' => 'Total Subscriptions', 'received' => 'Amount Received', 'outstanding' => 'Amount Outstanding'];

        // Standard PDF / CSV export of the current view.
        $format = (string) $request->input('format', '');
        if ($format === 'pdf' || $format === 'csv') {
            $columns = ['Sl No.', 'Member No.', 'Member', 'Due date', 'Amount', 'Received', 'Balance', 'Status'];
            $data = [];
            $sl = 0;
            foreach ($rows as $r) {
                $paid = (float) $r['paid'];
                $bal = (float) $r['balance'];
                $status = $bal <= 0.005 ? 'Paid' : ($paid > 0 ? 'Partial' : 'Pending');
                $data[] = [
                    ++$sl,
                    $r['member_number'] ?: '-',
                    $r['member_name'],
                    format_date($r['due_date']),
                    number_format((float) $r['amount'], 2),
                    number_format($paid, 2),
                    number_format($bal, 2),
                    $status,
                ];
            }
            $meta = array_filter([
                'Financial year' => $selectedFy['label'] ?? 'All years',
                'View'           => $labels[$view],
            ]);
            $summaryLines = [
                'Total subscriptions' => number_format((float) ($summary['total_amount'] ?? 0), 2) . ' (' . (int) ($summary['total_count'] ?? 0) . ')',
                'Amount received'     => number_format((float) ($summary['received_amount'] ?? 0), 2) . ' (' . (int) ($summary['received_count'] ?? 0) . ')',
                'Amount outstanding'  => number_format((float) ($summary['outstanding_amount'] ?? 0), 2) . ' (' . (int) ($summary['outstanding_count'] ?? 0) . ')',
            ];
            $filename = 'subscriptions-' . $view;
            if ($format === 'pdf') {
                // Open a print-preview page (new tab) with Close + Save-as-PDF,
                // reusing the standard report styling.
                $html = $this->pdf()->buildHtml('Subscriptions — ' . $labels[$view], $columns, $data, $meta, $summaryLines);
                $toolbar = '<div class="noprint" style="position:sticky;top:0;z-index:10;background:#fff;border-bottom:1px solid #e5e7eb;padding:10px 16px;text-align:right">'
                    . '<button type="button" onclick="window.print()" style="background:#047857;color:#fff;border:0;border-radius:6px;padding:8px 14px;font-size:13px;cursor:pointer">Print / Save as PDF</button> '
                    . '<button type="button" onclick="window.close()" style="background:#e5e7eb;color:#111827;border:0;border-radius:6px;padding:8px 14px;font-size:13px;cursor:pointer;margin-left:6px">Close</button>'
                    . '</div>';
                $html = str_replace('</style>', ' @media print { .noprint { display: none !important; } } </style>', $html);
                $html = preg_replace('/<body>/', '<body>' . $toolbar, $html, 1);
                while (ob_get_level() > 0) {
                    ob_end_clean();
                }
                header('Content-Type: text/html; charset=UTF-8');
                echo $html;
                exit;
            }
            CsvExporter::download($filename, $columns, $data);
        }

        $this->view('dashboard.subscriptions', [
            'title'          => 'Subscriptions',
            'view'           => $view,
            'summary'        => $summary,
            'rows'           => $rows,
            'financialYears' => $financialYears,
            'selectedFy'     => $selectedFy,
            'fyParam'        => $fyParam,
        ]);
    }

    private function pdf(): PdfReport
    {
        $assocId = Auth::associationId();
        $association = $assocId ? (new Association())->find($assocId) : null;
        $name = $association['name'] ?? 'Habitract';
        $logo = null;
        if (!empty($association['logo_path'])) {
            $candidate = (new ImageUploader())->baseDir() . '/' . $association['logo_path'];
            if (is_file($candidate)) {
                $logo = $candidate;
            }
        }
        return new PdfReport($name, $logo);
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
