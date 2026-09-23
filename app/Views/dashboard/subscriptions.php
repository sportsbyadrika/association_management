<?php $this->layout('layouts.app');
/** @var string $view */ /** @var array $summary */ /** @var list $rows */
/** @var list $financialYears */ /** @var array|null $selectedFy */ /** @var mixed $fyParam */
$canNavigate = \App\Core\Auth::is('association_admin', 'association_staff');
$fyValue = $fyParam !== null && $fyParam !== '' ? (string) $fyParam : (string) ($selectedFy['id'] ?? '');
$fyQ = $fyValue !== '' ? '&fy=' . urlencode($fyValue) : '';
$tabs = [
    'total'       => ['Total Subscriptions', (float) ($summary['total_amount'] ?? 0), (int) ($summary['total_count'] ?? 0)],
    'received'    => ['Amount Received', (float) ($summary['received_amount'] ?? 0), (int) ($summary['received_count'] ?? 0)],
    'outstanding' => ['Amount Outstanding', (float) ($summary['outstanding_amount'] ?? 0), (int) ($summary['outstanding_count'] ?? 0)],
];
$statusBadge = static fn (string $s): string => [
    'paid'    => 'bg-brand-100 text-brand-800',
    'partial' => 'bg-blue-100 text-blue-800',
    'pending' => 'bg-amber-100 text-amber-800',
][$s] ?? 'bg-gray-100 text-gray-600';
?>

<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <a href="<?= e(url('/dashboard')) ?>" class="text-sm text-gray-500 hover:text-brand-700">&larr; Dashboard</a>
        <h1 class="mt-1 text-2xl font-bold text-gray-900">Subscriptions</h1>
        <p class="mt-1 text-sm text-gray-500">Member-wise subscription dues<?= $selectedFy ? ' · ' . e($selectedFy['label']) : '' ?>. Choose a view below.</p>
    </div>
    <div class="flex gap-2">
        <a href="<?= e(url('/dashboard/subscriptions?view=' . $view . $fyQ . '&format=csv')) ?>" class="btn-secondary btn-sm">CSV</a>
        <a href="<?= e(url('/dashboard/subscriptions?view=' . $view . $fyQ . '&format=pdf')) ?>" class="btn-primary btn-sm">PDF</a>
    </div>
</div>

<!-- Summary cards double as the view switcher -->
<div class="grid gap-4 sm:grid-cols-3">
    <?php foreach ($tabs as $key => [$label, $amount, $count]): ?>
        <?php $active = $view === $key; ?>
        <a href="<?= e(url('/dashboard/subscriptions?view=' . $key . $fyQ)) ?>"
           class="card card-body block transition hover:shadow-md <?= $active ? 'ring-2 ring-brand-500' : '' ?>">
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-gray-500"><?= e($label) ?></p>
                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600"><?= number_format($count) ?></span>
            </div>
            <p class="mt-2 text-2xl font-bold <?= $key === 'received' ? 'text-brand-700' : ($key === 'outstanding' ? 'text-amber-600' : 'text-gray-900') ?>">₹ <?= money($amount) ?></p>
        </a>
    <?php endforeach; ?>
</div>

<div class="mt-6 card overflow-hidden">
    <div class="border-b border-gray-100 px-6 py-4">
        <h2 class="font-semibold text-gray-900"><?= e($tabs[$view][0]) ?> <span class="text-sm font-normal text-gray-400">(<?= count($rows) ?>)</span></h2>
    </div>
    <div class="overflow-x-auto">
        <table class="table">
            <thead><tr>
                <th>Member No.</th><th>Member</th><th>Due date</th>
                <th class="text-right">Amount</th><th class="text-right">Received</th><th class="text-right">Balance</th><th>Status</th>
            </tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <?php
                $paid = (float) $r['paid'];
                $bal = (float) $r['balance'];
                $status = $bal <= 0.005 ? 'paid' : ($paid > 0 ? 'partial' : 'pending');
                ?>
                <tr>
                    <td class="text-gray-700"><?= e($r['member_number'] ?: '—') ?></td>
                    <td class="font-medium text-gray-900">
                        <?php if ($canNavigate): ?>
                            <a href="<?= e(url('/members/' . $r['member_id'])) ?>" class="text-brand-700 hover:underline"><?= e($r['member_name']) ?></a>
                        <?php else: ?>
                            <?= e($r['member_name']) ?>
                        <?php endif; ?>
                    </td>
                    <td><?= e(format_date($r['due_date'])) ?></td>
                    <td class="text-right">₹ <?= money($r['amount']) ?></td>
                    <td class="text-right text-brand-700">₹ <?= money($paid) ?></td>
                    <td class="text-right <?= $bal > 0 ? 'text-amber-600' : 'text-gray-400' ?>">₹ <?= money($bal) ?></td>
                    <td><span class="badge capitalize <?= $statusBadge($status) ?>"><?= $status ?></span></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($rows === []): ?>
                <tr><td colspan="7" class="text-center text-gray-400 py-8">No subscription dues in this view.</td></tr>
            <?php endif; ?>
            </tbody>
            <?php if ($rows !== []): ?>
            <tfoot>
                <tr class="bg-gray-50 font-semibold">
                    <td colspan="3" class="text-right">Total</td>
                    <td class="text-right">₹ <?= money(array_sum(array_map(static fn ($r) => (float) $r['amount'], $rows))) ?></td>
                    <td class="text-right">₹ <?= money(array_sum(array_map(static fn ($r) => (float) $r['paid'], $rows))) ?></td>
                    <td class="text-right">₹ <?= money(array_sum(array_map(static fn ($r) => (float) $r['balance'], $rows))) ?></td>
                    <td></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>
