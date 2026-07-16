<?php $this->layout('layouts.app'); /** @var array $stats */ ?>

<h1 class="mb-1 text-2xl font-bold text-gray-900">Dashboard</h1>
<p class="mb-6 text-sm text-gray-500">Overview of your association's activity.</p>

<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <?php
    $cards = [
        ['Members', number_format($stats['members']), 'text-brand-700', '/members'],
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
    <p class="text-sm font-medium text-gray-500">Outstanding member dues (demands − receipts)</p>
    <p class="mt-1 text-xl font-bold <?= $stats['outstanding'] > 0 ? 'text-amber-600' : 'text-brand-700' ?>">₹ <?= money(max(0, $stats['outstanding'])) ?></p>
</div>

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
