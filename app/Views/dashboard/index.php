<?php $this->layout('layouts.app'); /** @var array $stats */ /** @var list $memberTypeCounts */
/** @var list $financialYears */ /** @var array|null $selectedFy */ /** @var mixed $fyParam */
$memberTypeCounts = $memberTypeCounts ?? [];
$financialYears = $financialYears ?? [];
// Officials get a read-only dashboard: cards are not links (no access beyond
// Dashboard + Reports).
$canNavigate = \App\Core\Auth::is('association_admin', 'association_staff');
$cardTag = $canNavigate ? 'a' : 'div';
$fyValue = $fyParam !== null && $fyParam !== '' ? (string) $fyParam : (string) ($selectedFy['id'] ?? '');
$fyQ = $fyValue !== '' ? '&fy=' . urlencode($fyValue) : '';
?>

<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
        <p class="mt-1 text-sm text-gray-500">Overview of your association's activity.</p>
    </div>
    <?php
    // Carry the selected financial year's date range so the report opens
    // scoped to the same period as the dashboard (totals then tally).
    $ieQs = $selectedFy
        ? '?from=' . urlencode((string) ($selectedFy['start_date'] ?? '')) . '&to=' . urlencode((string) ($selectedFy['end_date'] ?? ''))
        : '';
    ?>
    <div class="flex flex-wrap items-end gap-2">
        <a href="<?= e(url('/reports/income-expenditure' . $ieQs)) ?>" class="btn-secondary btn-sm">Income &amp; Expenditure</a>
        <?php if ($financialYears !== []): ?>
            <form method="get" action="<?= e(url('/dashboard')) ?>" class="flex items-end gap-2">
                <div>
                    <label for="fy" class="form-label">Financial year</label>
                    <select id="fy" name="fy" class="form-select" onchange="this.form.submit()">
                        <?php foreach ($financialYears as $fy): ?>
                            <option value="<?= (int) $fy['id'] ?>" <?= $fyValue === (string) $fy['id'] ? 'selected' : '' ?>><?= e($fy['label']) ?></option>
                        <?php endforeach; ?>
                        <option value="all" <?= (string) $fyParam === 'all' ? 'selected' : '' ?>>All years</option>
                    </select>
                </div>
                <noscript><button type="submit" class="btn-secondary btn-sm">Apply</button></noscript>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php if ($selectedFy !== null): ?>
    <p class="mb-4 text-xs text-gray-400">Amounts below are for <span class="font-medium text-gray-600"><?= e($selectedFy['label']) ?></span>. Members and project counts show current totals.</p>
<?php endif; ?>

<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <!-- Members card with per-type breakdown -->
    <<?= $cardTag ?> <?= $canNavigate ? 'href="' . e(url('/members')) . '"' : '' ?> class="card card-body block transition hover:shadow-md">
        <p class="text-sm font-medium text-gray-500">Members</p>
        <p class="mt-2 text-2xl font-bold text-brand-700"><?= e(number_format($stats['members'])) ?></p>
        <?php if ($memberTypeCounts !== []): ?>
            <div class="mt-3 flex flex-wrap gap-1.5">
                <?php foreach ($memberTypeCounts as $mt): ?>
                    <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">
                        <?= e($mt['type']) ?> <span class="font-semibold text-gray-800"><?= (int) $mt['count'] ?></span>
                    </span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </<?= $cardTag ?>>
    <?php
    $cards = [
        ['Total Receipts', '₹ ' . money($stats['receipts']), 'text-emerald-600', '/receipts'],
        ['Total Expenditure', '₹ ' . money($stats['expenditures']), 'text-red-600', '/expenditures'],
    ];
    foreach ($cards as [$label, $value, $color, $href]): ?>
        <<?= $cardTag ?> <?= $canNavigate ? 'href="' . e(url($href)) . '"' : '' ?> class="card card-body block transition hover:shadow-md">
            <p class="text-sm font-medium text-gray-500"><?= e($label) ?></p>
            <p class="mt-2 text-2xl font-bold <?= $color ?>"><?= e($value) ?></p>
        </<?= $cardTag ?>>
    <?php endforeach; ?>

    <!-- Activities: Projects / Gifts / Events (active / total), each links out -->
    <div class="card card-body">
        <p class="text-sm font-medium text-gray-500">Activities</p>
        <dl class="mt-2 space-y-1 text-sm">
            <?php
            $activityRows = [
                ['Projects', (int) $stats['projects'], (int) ($stats['projects_total'] ?? $stats['projects']), '/projects'],
                ['Gifts', (int) ($stats['gifts'] ?? 0), (int) ($stats['gifts_total'] ?? 0), '/gifts'],
                ['Events', (int) ($stats['events'] ?? 0), (int) ($stats['events_total'] ?? 0), '/events'],
            ];
            foreach ($activityRows as [$label, $active, $total, $href]):
                $rowTag = $canNavigate ? 'a' : 'div';
            ?>
                <<?= $rowTag ?> <?= $canNavigate ? 'href="' . e(url($href)) . '"' : '' ?>
                    class="flex items-center justify-between rounded-md px-2 py-1 <?= $canNavigate ? 'hover:bg-brand-50' : '' ?>">
                    <span class="text-gray-600"><?= e($label) ?></span>
                    <span class="font-bold text-indigo-600"><?= number_format($active) ?> / <?= number_format($total) ?></span>
                </<?= $rowTag ?>>
            <?php endforeach; ?>
        </dl>
    </div>
</div>

<div class="mt-4 grid gap-4 sm:grid-cols-3">
    <?php
    $subscription = $subscription ?? [];
    $subCards = [
        ['Total Subscriptions', (float) ($subscription['total_amount'] ?? 0), (int) ($subscription['total_count'] ?? 0), 'text-gray-900', 'total'],
        ['Amount Received', (float) ($subscription['received_amount'] ?? 0), (int) ($subscription['received_count'] ?? 0), 'text-brand-700', 'received'],
        ['Amount Outstanding', (float) ($subscription['outstanding_amount'] ?? 0), (int) ($subscription['outstanding_count'] ?? 0), 'text-amber-600', 'outstanding'],
    ];
    foreach ($subCards as [$label, $amount, $count, $color, $view]): ?>
        <a href="<?= e(url('/dashboard/subscriptions?view=' . $view . $fyQ)) ?>" class="card card-body block transition hover:shadow-md">
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-gray-500"><?= e($label) ?></p>
                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600"><?= number_format($count) ?></span>
            </div>
            <p class="mt-2 text-2xl font-bold <?= $color ?>">₹ <?= money($amount) ?></p>
            <p class="mt-1 text-xs text-brand-600">View list →</p>
        </a>
    <?php endforeach; ?>
</div>
<p class="mt-2 text-xs text-gray-400">Subscription dues across all members. Click a card for the member-wise list.</p>

<div class="mt-8 grid gap-6 lg:grid-cols-2">
    <div class="card">
        <div class="border-b border-gray-100 px-6 py-4">
            <h2 class="font-semibold text-gray-900">Recent receipts</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>Date</th><th>Member</th><th class="text-right">Amount</th></tr></thead>
                <tbody>
                <?php foreach ($recentReceipts as $r): ?>
                    <tr>
                        <td><?= e(format_date($r['received_on'])) ?></td>
                        <td><?= e($r['member_name'] ?? '—') ?></td>
                        <td class="text-right font-medium">₹ <?= money($r['amount']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($recentReceipts === []): ?>
                    <tr><td colspan="3" class="text-center text-gray-400">No receipts yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="border-b border-gray-100 px-6 py-4">
            <h2 class="font-semibold text-gray-900">Projects by type</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>Project Type</th><th class="text-right">Count</th><th class="text-right">Target</th><th class="text-right">Collected</th></tr></thead>
                <tbody>
                <?php $ptTotals = ['count' => 0, 'target' => 0.0, 'collected' => 0.0]; ?>
                <?php foreach (($projectTypeSummary ?? []) as $pt): ?>
                    <?php $ptTotals['count'] += (int) $pt['count']; $ptTotals['target'] += (float) $pt['target']; $ptTotals['collected'] += (float) $pt['collected']; ?>
                    <tr>
                        <td class="font-medium text-gray-900"><?= e($pt['type']) ?></td>
                        <td class="text-right"><?= (int) $pt['count'] ?></td>
                        <td class="text-right">₹ <?= money($pt['target']) ?></td>
                        <td class="text-right font-medium text-brand-700">₹ <?= money($pt['collected']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (($projectTypeSummary ?? []) === []): ?>
                    <tr><td colspan="4" class="text-center text-gray-400">No projects yet.</td></tr>
                <?php endif; ?>
                </tbody>
                <?php if (($projectTypeSummary ?? []) !== []): ?>
                <tfoot>
                    <tr class="bg-gray-50 font-semibold">
                        <td class="text-right">Total</td>
                        <td class="text-right"><?= (int) $ptTotals['count'] ?></td>
                        <td class="text-right">₹ <?= money($ptTotals['target']) ?></td>
                        <td class="text-right">₹ <?= money($ptTotals['collected']) ?></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>
