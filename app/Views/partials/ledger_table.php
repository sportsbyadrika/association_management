<?php /** @var array $ledger  keys: rows, total_demand, total_paid, balance */ ?>
<div class="grid gap-4 sm:grid-cols-3">
    <div class="card card-body">
        <p class="text-sm text-gray-500">Total demanded</p>
        <p class="mt-1 text-xl font-bold text-gray-900">₹ <?= money($ledger['total_demand']) ?></p>
    </div>
    <div class="card card-body">
        <p class="text-sm text-gray-500">Total paid</p>
        <p class="mt-1 text-xl font-bold text-brand-700">₹ <?= money($ledger['total_paid']) ?></p>
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
                </tr>
            </thead>
            <tbody>
            <?php foreach ($ledger['rows'] as $row): ?>
                <tr>
                    <td><?= e(format_date($row['date'])) ?></td>
                    <td>
                        <span class="badge <?= $row['type'] === 'Receipt' ? 'bg-brand-100 text-brand-800' : 'bg-amber-100 text-amber-800' ?>"><?= e($row['type']) ?></span>
                    </td>
                    <td class="text-gray-600"><?= e($row['description']) ?></td>
                    <td class="text-right"><?= $row['debit'] > 0 ? '₹ ' . money($row['debit']) : '—' ?></td>
                    <td class="text-right"><?= $row['credit'] > 0 ? '₹ ' . money($row['credit']) : '—' ?></td>
                    <td class="text-right font-medium">₹ <?= money($row['balance']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($ledger['rows'] === []): ?>
                <tr><td colspan="6" class="text-center text-gray-400 py-8">No ledger entries yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
