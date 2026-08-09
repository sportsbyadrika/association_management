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
        $rows = (new Member())->directoryForReport($assocId);
        $format = (string) $request->input('format', 'csv');
        $layout = (string) $request->input('layout', 'list');

        // CSV: enriched text columns (no photo).
        if ($format !== 'pdf') {
            $columns = ['Sl No.', 'Member Id', 'Name', 'Type', 'Age', 'Gender', 'Blood Group',
                        'Mobile', 'WhatsApp', 'Email', 'Family', 'Additional Memberships', 'Status'];
            $data = [];
            $sl = 0;
            foreach ($rows as $r) {
                $data[] = [
                    ++$sl,
                    $r['member_number'] ?: '-',
                    $r['name'],
                    $r['type'] ?? '',
                    $r['age'] ?? '',
                    ucfirst((string) ($r['gender'] ?? '')),
                    $r['blood_group'] ?? '',
                    $r['mobile'] ?? '',
                    $r['whatsapp'] ?? '',
                    $r['email'] ?? '',
                    $r['family_members_count'] ?? '',
                    $r['additional_memberships'] ?? '',
                    (int) $r['is_active'] === 1 ? 'Active' : 'Inactive',
                ];
            }
            CsvExporter::download('members-report', $columns, $data);
        }

        // PDF: list-wise (table with photos) or card-wise (ID cards).
        $meta = ['Total members' => (string) count($rows)];
        if ($layout === 'card') {
            $this->pdf()->streamHtml('members-cards', 'Members — ID Cards', $this->membersCardHtml($rows), $meta, 'portrait');
        }
        $this->pdf()->streamHtml('members-directory', 'Members Directory', $this->membersListHtml($rows), $meta, 'landscape');
    }

    /** Base64 data URI for a stored member photo, or null. */
    private function photoDataUri(?string $relativePath): ?string
    {
        if (!$relativePath) {
            return null;
        }
        $base = (new ImageUploader())->baseDir();
        $path = $base . '/' . ltrim($relativePath, '/');
        $real = realpath($path);
        if ($real === false || !str_starts_with($real, realpath($base) ?: $base) || !is_file($real)) {
            return null;
        }
        $data = @file_get_contents($real);
        if ($data === false) {
            return null;
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($data) ?: 'image/jpeg';
        return 'data:' . $mime . ';base64,' . base64_encode($data);
    }

    /** @param list<array<string,mixed>> $rows */
    private function membersListHtml(array $rows): string
    {
        $esc = static fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
        $head = '<table class="data"><thead><tr>'
            . '<th>Sl No.</th><th>Member Id</th><th>Photo</th><th>Name</th><th>Type</th>'
            . '<th>Age</th><th>Gender</th><th>Blood</th><th>Mobile</th><th>WhatsApp</th>'
            . '<th>Email</th><th>Family</th><th>Additional Memberships</th><th>Status</th>'
            . '</tr></thead><tbody>';
        $body = '';
        $sl = 0;
        foreach ($rows as $r) {
            $uri = $this->photoDataUri($r['photo_path'] ?? null);
            $photo = $uri !== null ? '<img class="photo" src="' . $uri . '">' : '—';
            $body .= '<tr>'
                . '<td>' . (++$sl) . '</td>'
                . '<td>' . $esc($r['member_number'] ?: '-') . '</td>'
                . '<td>' . $photo . '</td>'
                . '<td>' . $esc($r['name']) . '</td>'
                . '<td>' . $esc($r['type'] ?? '') . '</td>'
                . '<td>' . $esc($r['age'] ?? '') . '</td>'
                . '<td>' . $esc(ucfirst((string) ($r['gender'] ?? ''))) . '</td>'
                . '<td>' . $esc($r['blood_group'] ?? '') . '</td>'
                . '<td>' . $esc($r['mobile'] ?? '') . '</td>'
                . '<td>' . $esc($r['whatsapp'] ?? '') . '</td>'
                . '<td>' . $esc($r['email'] ?? '') . '</td>'
                . '<td>' . $esc($r['family_members_count'] ?? '') . '</td>'
                . '<td>' . $esc($r['additional_memberships'] ?? '') . '</td>'
                . '<td>' . ((int) $r['is_active'] === 1 ? 'Active' : 'Inactive') . '</td>'
                . '</tr>';
        }
        if ($rows === []) {
            $body = '<tr><td colspan="14" style="text-align:center;color:#9ca3af">No members found.</td></tr>';
        }
        return $head . $body . '</tbody></table>';
    }

    /** @param list<array<string,mixed>> $rows */
    private function membersCardHtml(array $rows): string
    {
        $esc = static fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
        if ($rows === []) {
            return '<p style="text-align:center;color:#9ca3af">No members found.</p>';
        }
        $cells = [];
        foreach ($rows as $r) {
            $uri = $this->photoDataUri($r['photo_path'] ?? null);
            $pic = $uri !== null
                ? '<img src="' . $uri . '">'
                : '<div class="ph">' . $esc(strtoupper(substr((string) $r['name'], 0, 1))) . '</div>';
            $lines = '';
            $lines .= '<div class="fld"><b>Member Id:</b> ' . $esc($r['member_number'] ?: '-') . '</div>';
            if (!empty($r['blood_group'])) {
                $lines .= '<div class="fld"><b>Blood:</b> ' . $esc($r['blood_group']) . '</div>';
            }
            if (!empty($r['mobile'])) {
                $lines .= '<div class="fld"><b>Mobile:</b> ' . $esc($r['mobile']) . '</div>';
            }
            if (!empty($r['additional_memberships'])) {
                $lines .= '<div class="fld"><b>Also:</b> ' . $esc($r['additional_memberships']) . '</div>';
            }
            $cells[] = '<td class="card"><div class="row">'
                . '<div class="pic">' . $pic . '</div>'
                . '<div class="info">'
                . '<div class="nm">' . $esc($r['name']) . '</div>'
                . '<div class="ty">' . $esc($r['type'] ?? 'Member') . '</div>'
                . $lines
                . '</div></div></td>';
        }
        // Two cards per row.
        $html = '<table class="cards"><tbody>';
        for ($i = 0, $n = count($cells); $i < $n; $i += 2) {
            $left = $cells[$i];
            $right = $cells[$i + 1] ?? '<td></td>';
            $html .= '<tr>' . $left . $right . '</tr>';
        }
        $html .= '</tbody></table>';
        return $html;
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
            'Total dues' => number_format($ledger['total_demand'], 2),
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

    // ---- 4b. Income & Expenditure (project-wise) -----------------------

    public function incomeExpenditure(Request $request): void
    {
        $assocId = Auth::associationId();
        [$from, $to] = $this->dateRange($request);

        $data = (new Project())->incomeExpenditureByProject($assocId, $from, $to);

        // Assemble display rows: each project, then a general/non-project row.
        $rows = [];
        foreach ($data['rows'] as $r) {
            $income = (float) $r['income'];
            $expense = (float) $r['expense'];
            $rows[] = [
                'project' => $r['name'],
                'income'  => $income,
                'expense' => $expense,
                'net'     => $income - $expense,
            ];
        }
        $gen = $data['general'];
        if ($gen['income'] > 0 || $gen['expense'] > 0) {
            $rows[] = [
                'project' => 'General / Non-project',
                'income'  => (float) $gen['income'],
                'expense' => (float) $gen['expense'],
                'net'     => (float) $gen['income'] - (float) $gen['expense'],
            ];
        }

        $totals = ['income' => 0.0, 'expense' => 0.0, 'net' => 0.0];
        foreach ($rows as $r) {
            $totals['income'] += $r['income'];
            $totals['expense'] += $r['expense'];
            $totals['net'] += $r['net'];
        }

        $format = (string) $request->input('format', '');
        if ($format === 'csv' || $format === 'pdf') {
            $columns = ['Sl No.', 'Project', 'Income', 'Expense', 'Net'];
            $out = [];
            $sl = 0;
            foreach ($rows as $r) {
                $out[] = [
                    ++$sl,
                    $r['project'],
                    number_format($r['income'], 2),
                    number_format($r['expense'], 2),
                    number_format($r['net'], 2),
                ];
            }
            // Grand total row.
            $out[] = [
                '', 'Grand Total',
                number_format($totals['income'], 2),
                number_format($totals['expense'], 2),
                number_format($totals['net'], 2),
            ];
            $meta = $this->rangeMeta($from, $to);
            $summary = [
                'Total income'  => number_format($totals['income'], 2),
                'Total expense' => number_format($totals['expense'], 2),
                'Net'           => number_format($totals['net'], 2),
            ];
            $this->emit($request, 'income-expenditure-report', 'Income & Expenditure Report', $columns, $out, $meta, $summary);
        }

        $this->view('reports.income_expenditure', [
            'title'  => 'Income & Expenditure Report',
            'rows'   => $rows,
            'totals' => $totals,
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
                $columns = ['Sl No.', 'Member No.', 'Name', 'Total Dues', 'Collected', 'Balance', 'Last Received'];
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
                    'Total dues' => number_format($totals['demand'], 2),
                    'Collected'    => number_format($totals['collected'], 2),
                    'Balance'      => number_format($totals['balance'], 2),
                ];
                $this->emit($request, 'ledger-' . $slug . '-members',
                    $purposeName . ' Ledger — ' . $projectName, $columns, $data, $meta, $summary);
            }

            $this->view('reports.purpose_ledger_members', [
                'title'           => 'Ledger',
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
            $columns = ['Sl No.', 'Project', 'Members', 'Total Dues', 'Collections', 'Collected', 'Pending', 'Balance'];
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
                'Total dues' => number_format($totals['demand'], 2),
                'Collected'    => number_format($totals['collected'], 2),
                'Balance'      => number_format($totals['balance'], 2),
            ];
            $this->emit($request, 'ledger-' . $slug,
                $purposeName . ' Ledger', $columns, $data, $meta, $summary);
        }

        $this->view('reports.purpose_ledger', [
            'title'           => 'Ledger',
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
