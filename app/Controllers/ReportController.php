<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Association;
use App\Models\Demand;
use App\Models\DemandPurpose;
use App\Models\Expenditure;
use App\Models\FinancialYear;
use App\Models\Member;
use App\Models\Project;
use App\Models\Receipt;
use App\Services\CsvExporter;
use App\Services\ImageUploader;
use App\Services\MemberLedger;
use App\Services\PdfReport;

/**
 * Reporting layer. Each report defines its columns + rows; CsvExporter and
 * PdfReport render the same dataset. Reports are scoped to the association.
 */
final class ReportController extends Controller
{
    public function index(Request $request): void
    {
        $this->view('reports.index', ['title' => 'Reports']);
    }

    // ---- 1. Members directory ------------------------------------------

    public function members(Request $request): void
    {
        $assocId = Auth::associationId();
        $rows = (new Member())->db()->fetchAll(
            "SELECT m.name, mt.name AS type, m.age, m.gender, m.mobile, m.whatsapp,
                    m.email, m.family_members_count, m.is_active
             FROM members m LEFT JOIN member_types mt ON mt.id = m.member_type_id
             WHERE m.association_id = ? ORDER BY m.name ASC",
            [$assocId]
        );

        $columns = ['Name', 'Type', 'Age', 'Gender', 'Mobile', 'WhatsApp', 'Email', 'Family', 'Status'];
        $data = array_map(static fn ($r) => [
            $r['name'], $r['type'] ?? '', $r['age'] ?? '', ucfirst((string) ($r['gender'] ?? '')),
            $r['mobile'] ?? '', $r['whatsapp'] ?? '', $r['email'] ?? '',
            $r['family_members_count'] ?? '', (int) $r['is_active'] === 1 ? 'Active' : 'Inactive',
        ], $rows);

        $this->emit($request, 'members-report', 'Members Directory', $columns, $data);
    }

    // ---- 2. Member ledger ----------------------------------------------

    public function memberLedger(Request $request): void
    {
        $assocId = Auth::associationId();
        $memberId = (int) $request->input('member_id', 0);
        $member = (new Member())->findWithType($memberId, $assocId);
        if ($member === null) {
            Response::notFound('Member not found.');
        }
        $ledger = (new MemberLedger())->build($memberId);

        $columns = ['Date', 'Type', 'Description', 'Debit', 'Credit', 'Balance'];
        $data = array_map(static fn ($r) => [
            format_date($r['date']), $r['type'], $r['description'],
            $r['debit'] > 0 ? number_format($r['debit'], 2) : '',
            $r['credit'] > 0 ? number_format($r['credit'], 2) : '',
            number_format($r['balance'], 2),
        ], $ledger['rows']);

        $summary = [
            'Total demanded' => number_format($ledger['total_demand'], 2),
            'Total paid'     => number_format($ledger['total_paid'], 2),
            'Balance'        => number_format($ledger['balance'], 2),
        ];

        $this->emit($request, 'member-ledger-' . $memberId, 'Member Ledger — ' . $member['name'], $columns, $data, [], $summary);
    }

    // ---- 3. Income report ----------------------------------------------

    public function income(Request $request): void
    {
        $assocId = Auth::associationId();
        [$from, $to] = $this->dateRange($request);
        $report = (new Receipt())->incomeReport($assocId, $from, $to);
        $detail = (new Receipt())->detailReport($assocId, $from, $to);

        $format = (string) $request->input('format', '');
        if ($format === 'csv' || $format === 'pdf') {
            $columns = ['Date', 'Member', 'Income Head', 'Project', 'Mode', 'Amount'];
            $data = array_map(static fn ($r) => [
                format_date($r['received_on']), $r['member_name'] ?? '', $r['income_head_name'] ?? '',
                $r['project_name'] ?? 'General', str_replace('_', ' ', (string) $r['mode']),
                number_format((float) $r['amount'], 2),
            ], $detail);
            $meta = $this->rangeMeta($from, $to);
            $summary = ['Total income' => number_format($report['total'], 2)];
            $this->emit($request, 'income-report', 'Income Report', $columns, $data, $meta, $summary);
        }

        $this->view('reports.income', [
            'title'  => 'Income Report',
            'report' => $report,
            'from'   => $from,
            'to'     => $to,
        ]);
    }

    // ---- 4. Expenditure report -----------------------------------------

    public function expenditure(Request $request): void
    {
        $assocId = Auth::associationId();
        [$from, $to] = $this->dateRange($request);
        $report = (new Expenditure())->expenditureReport($assocId, $from, $to);
        $detail = (new Expenditure())->detailReport($assocId, $from, $to);

        $format = (string) $request->input('format', '');
        if ($format === 'csv' || $format === 'pdf') {
            $columns = ['Date', 'Head', 'Project', 'Category', 'Mode', 'Amount'];
            $data = array_map(static fn ($r) => [
                format_date($r['paid_on']), $r['head_name'] ?? '', $r['project_name'] ?? '',
                ucfirst((string) $r['category']), str_replace('_', ' ', (string) $r['mode']),
                number_format((float) $r['amount'], 2),
            ], $detail);
            $meta = $this->rangeMeta($from, $to);
            $summary = ['Total expenditure' => number_format($report['total'], 2)];
            $this->emit($request, 'expenditure-report', 'Expenditure Report', $columns, $data, $meta, $summary);
        }

        $this->view('reports.expenditure', [
            'title'  => 'Expenditure Report',
            'report' => $report,
            'from'   => $from,
            'to'     => $to,
        ]);
    }

    // ---- 5. Purpose (e.g. Subscription) ledger -------------------------

    public function purposeLedger(Request $request): void
    {
        $assocId = Auth::associationId();
        $purposes = (new DemandPurpose())->allForAssociationOrdered($assocId);

        // Selected purpose — default to the first mandatory (usually
        // Subscription), else the first defined purpose.
        $purposeId = (int) $request->input('purpose_id', 0);
        if ($purposeId <= 0 || !$this->purposeExists($purposes, $purposeId)) {
            $default = null;
            foreach ($purposes as $p) {
                if ($p['type'] === 'mandatory') { $default = $p; break; }
            }
            $default = $default ?? ($purposes[0] ?? null);
            $purposeId = (int) ($default['id'] ?? 0);
        }
        $selectedPurpose = null;
        foreach ($purposes as $p) {
            if ((int) $p['id'] === $purposeId) { $selectedPurpose = $p; break; }
        }

        // Financial year filter (default the current one; 'all' = no bound).
        $fyModel = new FinancialYear();
        $financialYears = $fyModel->allForAssociationOrdered($assocId);
        $fyParam = $request->input('fy');
        $selectedFy = null;
        if ($fyParam === 'all') {
            $selectedFy = null;
        } elseif ($fyParam !== null && $fyParam !== '') {
            foreach ($financialYears as $fy) {
                if ((int) $fy['id'] === (int) $fyParam) { $selectedFy = $fy; break; }
            }
        } else {
            $selectedFy = $fyModel->current($assocId);
        }

        $from = $selectedFy['start_date'] ?? null;
        $to = $selectedFy['end_date'] ?? null;
        $purposeName = $selectedPurpose['name'] ?? 'Purpose';
        $rangeLabel = $selectedFy ? $selectedFy['label'] : 'All time';
        $format = (string) $request->input('format', '');
        $slug = strtolower(str_replace(' ', '-', (string) $purposeName));

        // Drill-down: when a project is selected, show the member-wise list for
        // that project (the "list" action on a project row).
        $projectParam = $request->input('project');
        $memberMode = $projectParam !== null && $projectParam !== '';

        if ($memberMode) {
            $projectId = $projectParam === 'none' ? null : (int) $projectParam;
            if ($projectId !== null) {
                $project = (new Project())->findWithType($projectId, $assocId);
                $projectName = $project['name'] ?? ('#' . $projectId);
            } else {
                $projectName = 'No project';
            }

            $rows = $purposeId > 0
                ? (new Demand())->purposeLedger($assocId, $purposeId, $from, $to, true, $projectId)
                : [];

            $totals = ['demand' => 0.0, 'collected' => 0.0, 'balance' => 0.0];
            foreach ($rows as $r) {
                $totals['demand'] += (float) $r['total_demand'];
                $totals['collected'] += (float) $r['collected'];
                $totals['balance'] += (float) $r['balance'];
            }

            if ($format === 'csv' || $format === 'pdf') {
                $columns = ['Sl No.', 'Member No.', 'Name', 'Total Demand', 'Collected', 'Balance', 'Last Received'];
                $data = [];
                $sl = 0;
                foreach ($rows as $r) {
                    $data[] = [
                        ++$sl,
                        $r['member_number'] ?: '-',
                        $r['name'],
                        number_format((float) $r['total_demand'], 2),
                        number_format((float) $r['collected'], 2),
                        number_format((float) $r['balance'], 2),
                        $r['last_received'] ? format_date($r['last_received']) : '-',
                    ];
                }
                $meta = [
                    'Purpose'    => $purposeName,
                    'Project'    => $projectName,
                    'Date range' => $rangeLabel,
                ];
                $summary = [
                    'Total demand' => number_format($totals['demand'], 2),
                    'Collected'    => number_format($totals['collected'], 2),
                    'Balance'      => number_format($totals['balance'], 2),
                ];
                $this->emit($request, 'ledger-' . $slug . '-members',
                    $purposeName . ' Ledger — ' . $projectName, $columns, $data, $meta, $summary);
            }

            $this->view('reports.purpose_ledger_members', [
                'title'           => 'Purpose Ledger',
                'purposeId'       => $purposeId,
                'selectedPurpose' => $selectedPurpose,
                'selectedFy'      => $selectedFy,
                'fyParam'         => $fyParam,
                'projectParam'    => (string) $projectParam,
                'projectName'     => $projectName,
                'rows'            => $rows,
                'totals'          => $totals,
            ]);
            return;
        }

        // Default: project-wise rollup for the selected purpose.
        $rows = $purposeId > 0
            ? (new Demand())->purposeProjectLedger($assocId, $purposeId, $from, $to)
            : [];

        $totals = ['members' => 0, 'demand' => 0.0, 'collections' => 0, 'collected' => 0.0, 'balance_count' => 0, 'balance' => 0.0];
        foreach ($rows as $r) {
            $totals['members'] += (int) $r['members_demanded'];
            $totals['demand'] += (float) $r['total_demand'];
            $totals['collections'] += (int) $r['collections_count'];
            $totals['collected'] += (float) $r['collected'];
            $totals['balance_count'] += (int) $r['balance_count'];
            $totals['balance'] += (float) $r['balance'];
        }

        if ($format === 'csv' || $format === 'pdf') {
            $columns = ['Sl No.', 'Project', 'Members', 'Total Demand', 'Collections', 'Collected', 'Pending', 'Balance'];
            $data = [];
            $sl = 0;
            foreach ($rows as $r) {
                $data[] = [
                    ++$sl,
                    $r['project_name'] ?: 'No project',
                    (int) $r['members_demanded'],
                    number_format((float) $r['total_demand'], 2),
                    (int) $r['collections_count'],
                    number_format((float) $r['collected'], 2),
                    (int) $r['balance_count'],
                    number_format((float) $r['balance'], 2),
                ];
            }
            $meta = [
                'Purpose'    => $purposeName,
                'Date range' => $rangeLabel,
            ];
            $summary = [
                'Total demand' => number_format($totals['demand'], 2),
                'Collected'    => number_format($totals['collected'], 2),
                'Balance'      => number_format($totals['balance'], 2),
            ];
            $this->emit($request, 'ledger-' . $slug,
                $purposeName . ' Ledger', $columns, $data, $meta, $summary);
        }

        $this->view('reports.purpose_ledger', [
            'title'           => 'Purpose Ledger',
            'purposes'        => $purposes,
            'purposeId'       => $purposeId,
            'selectedPurpose' => $selectedPurpose,
            'financialYears'  => $financialYears,
            'selectedFy'      => $selectedFy,
            'fyParam'         => $fyParam,
            'rows'            => $rows,
            'totals'          => $totals,
        ]);
    }

    /** @param list<array<string,mixed>> $purposes */
    private function purposeExists(array $purposes, int $id): bool
    {
        foreach ($purposes as $p) {
            if ((int) $p['id'] === $id) {
                return true;
            }
        }
        return false;
    }

    // ---- Shared render/emit --------------------------------------------

    /**
     * Emit CSV or PDF based on ?format; called by report actions.
     * @param list<string> $columns
     * @param list<array<int,mixed>> $data
     */
    private function emit(Request $request, string $filename, string $title, array $columns, array $data, array $meta = [], array $summary = []): void
    {
        $format = (string) $request->input('format', 'csv');
        if ($format === 'pdf') {
            $this->pdf()->stream($filename, $title, $columns, $data, $meta, $summary);
        }
        // default CSV
        CsvExporter::download($filename, $columns, $data);
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

    /** @return array{0:?string,1:?string} */
    private function dateRange(Request $request): array
    {
        $from = (string) $request->input('from', '');
        $to = (string) $request->input('to', '');
        return [
            $from !== '' && strtotime($from) ? $from : null,
            $to !== '' && strtotime($to) ? $to : null,
        ];
    }

    /** @return array<string,string> */
    private function rangeMeta(?string $from, ?string $to): array
    {
        if ($from === null && $to === null) {
            return ['Date range' => 'All time'];
        }
        return ['Date range' => ($from ? format_date($from) : 'Start') . ' → ' . ($to ? format_date($to) : 'Today')];
    }
}
