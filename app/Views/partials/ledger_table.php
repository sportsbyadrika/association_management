<?php
/** @var array $ledger  keys: rows, total_demand, total_paid, balance */
/** @var array|null $member */
$canRecord = \App\Core\Auth::is('association_admin', 'association_staff');
$memberId = $member['id'] ?? null;
$statusBadge = static fn (string $s): string => [
    'paid'    => 'bg-brand-100 text-brand-800',
    'partial' => 'bg-blue-100 text-blue-800',
    'pending' => 'bg-amber-100 text-amber-800',
][$s] ?? 'bg-gray-100 text-gray-600';
$typeBadge = static fn (string $t): string => [
    'Receipt'    => 'bg-brand-100 text-brand-800',
    'Demand'     => 'bg-amber-100 text-amber-800',
    'Adjustment' => 'bg-indigo-100 text-indigo-800',
][$t] ?? 'bg-gray-100 text-gray-600';
?>
<div class="grid gap-4 sm:grid-cols-3">
    <div class="card card-body">
        <p class="text-sm text-gray-500">Total demanded</p>
        <p class="mt-1 text-xl font-bold text-gray-900">₹ <?= money($ledger['total_demand']) ?></p>
    </div>
    <div class="card card-body">
        <p class="text-sm text-gray-500">Total paid</p>
        <p class="mt-1 text-xl font-bold text-brand-700">₹ <?= money($ledger['total_paid']) ?></p>
        <?php if (($ledger['total_adjusted'] ?? 0) > 0): ?>
            <p class="mt-1 text-xs text-indigo-600">+ ₹<?= money($ledger['total_adjusted']) ?> marked paid (no receipt)</p>
        <?php endif; ?>
    </div>
    <div class="card card-body">
        <p class="text-sm text-gray-500">Outstanding balance</p>
        <p class="mt-1 text-xl font-bold <?= $ledger['balance'] > 0 ? 'text-amber-600' : 'text-brand-700' ?>">₹ <?= money($ledger['balance']) ?></p>
    </div>
</div>

<div class="mt-4 card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="table">
            <thead>
                <tr>
                    <th>Date</th><th>Type</th><th>Description</th>
                    <th class="text-right">Debit</th><th class="text-right">Credit</th><th class="text-right">Balance</th>
                    <th>Status / Action</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($ledger['rows'] as $row): ?>
                <?php $isDemand = ($row['kind'] ?? '') === 'demand'; ?>
                <tr>
                    <td><?= e(format_date($row['date'])) ?></td>
                    <td>
                        <span class="badge <?= $typeBadge($row['type']) ?>"><?= e($row['type']) ?></span>
                    </td>
                    <td class="text-gray-600"><?= e($row['description']) ?></td>
                    <td class="text-right"><?= $row['debit'] > 0 ? '₹ ' . money($row['debit']) : '—' ?></td>
                    <td class="text-right"><?= $row['credit'] > 0 ? '₹ ' . money($row['credit']) : '—' ?></td>
                    <td class="text-right font-medium">₹ <?= money($row['balance']) ?></td>
                    <td>
                        <?php if ($isDemand): ?>
                            <span class="badge <?= $statusBadge($row['status']) ?> capitalize"><?= e($row['status']) ?></span>
                            <?php if ($canRecord && in_array($row['status'], ['pending', 'partial'], true)): ?>
                                <a href="<?= e(url('/receipts/create?demand_id=' . $row['demand_id'])) ?>"
                                   class="ml-2 text-sm font-medium text-brand-700 hover:underline">Record receipt<?= $row['remaining'] > 0 ? ' (₹' . money($row['remaining']) . ')' : '' ?></a>
                                <span class="text-gray-300">·</span>
                                <form method="post" action="<?= e(url('/demands/' . $row['demand_id'] . '/mark-paid')) ?>" class="inline" data-confirm="Mark this demand as paid without recording a receipt?">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="text-sm text-gray-500 hover:underline">Mark paid</button>
                                </form>
                            <?php elseif ($canRecord && ($row['reopenable'] ?? false)): ?>
                                <form method="post" action="<?= e(url('/demands/' . $row['demand_id'] . '/reopen')) ?>" class="inline" data-confirm="Reopen this demand? It was marked paid without a receipt.">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="ml-2 text-sm text-gray-500 hover:underline">Reopen</button>
                                </form>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="text-gray-300">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($ledger['rows'] === []): ?>
                <tr><td colspan="7" class="text-center text-gray-400 py-8">No ledger entries yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
