<?php $this->layout('layouts.app'); /** @var array $stats */ /** @var list $memberTypeCounts */
$memberTypeCounts = $memberTypeCounts ?? [];
?>

<h1 class="mb-1 text-2xl font-bold text-gray-900">Dashboard</h1>
<p class="mb-6 text-sm text-gray-500">Overview of your association's activity.</p>

<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <!-- Members card with per-type breakdown -->
    <a href="<?= e(url('/members')) ?>" class="card card-body block transition hover:shadow-md">
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
    </a>
    <?php
    $cards = [
        ['Total Receipts', '₹ ' . money($stats['receipts']), 'text-emerald-600', '/receipts'],
        ['Total Expenditure', '₹ ' . money($stats['expenditures']), 'text-red-600', '/expenditures'],
        ['Active Projects', number_format($stats['projects']), 'text-indigo-600', '/projects'],
    ];
    foreach ($cards as [$label, $value, $color, $href]): ?>
        <a href="<?= e(url($href)) ?>" class="card card-body block transition hover:shadow-md">
            <p class="text-sm font-medium text-gray-500"><?= e($label) ?></p>
            <p class="mt-2 text-2xl font-bold <?= $color ?>"><?= e($value) ?></p>
        </a>
    <?php endforeach; ?>
</div>

<div class="mt-4 card card-body">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm font-medium text-gray-500">Outstanding member dues (dues − receipts)</p>
            <p class="mt-1 text-2xl font-bold <?= $stats['outstanding'] > 0 ? 'text-amber-600' : 'text-brand-700' ?>">₹ <?= money(max(0, $stats['outstanding'])) ?></p>
        </div>
        <div class="grid grid-cols-2 gap-3 sm:w-96">
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3">
                <p class="text-xs font-medium uppercase tracking-wide text-red-700">Mandatory</p>
                <p class="mt-1 text-lg font-bold text-red-700">₹ <?= money(max(0, $stats['outstanding_mandatory'])) ?></p>
            </div>
            <div class="rounded-lg border border-sky-200 bg-sky-50 px-4 py-3">
                <p class="text-xs font-medium uppercase tracking-wide text-sky-700">Optional</p>
                <p class="mt-1 text-lg font-bold text-sky-700">₹ <?= money(max(0, $stats['outstanding_optional'])) ?></p>
            </div>
        </div>
    </div>
    <p class="mt-2 text-xs text-gray-400">Split by due purpose type — configure under Masters → Due Purpose.</p>
</div>

<a href="<?= e(url('/reports/purpose-ledger')) ?>" class="mt-4 card card-body block transition hover:shadow-md">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-sm font-medium text-gray-500">Subscription dues</p>
            <p class="mt-1 text-2xl font-bold <?= $stats['subscription_dues'] > 0 ? 'text-amber-600' : 'text-brand-700' ?>">₹ <?= money(max(0, $stats['subscription_dues'])) ?></p>
        </div>
        <span class="rounded-full bg-red-50 px-3 py-1 text-xs font-medium text-red-700">Subscription</span>
    </div>
    <p class="mt-2 text-xs text-gray-400">Outstanding for the Subscription purpose. Open the purpose ledger for the member-wise breakdown.</p>
</a>

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
            <h2 class="font-semibold text-gray-900">Projects</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>Project</th><th class="text-right">Target</th><th class="text-right">Collected</th></tr></thead>
                <tbody>
                <?php foreach ($projects as $p): ?>
                    <tr>
                        <td><a href="<?= e(url('/projects/' . $p['id'])) ?>" class="font-medium text-brand-700 hover:underline"><?= e($p['name']) ?></a></td>
                        <td class="text-right">₹ <?= money($p['target_amount']) ?></td>
                        <td class="text-right font-medium">₹ <?= money($p['collected']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($projects === []): ?>
                    <tr><td colspan="3" class="text-center text-gray-400">No projects yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
