<?php $this->layout('layouts.app'); /** @var array $account */ /** @var array $ledger */ ?>

<div class="mb-6 flex items-center justify-between">
    <a href="<?= e(url('/bank-accounts')) ?>" class="text-sm text-gray-500 hover:text-brand-700">&larr; Back to bank accounts</a>
    <a href="<?= e(url('/bank-accounts/' . $account['id'] . '/edit')) ?>" class="btn-primary btn-sm">Edit</a>
</div>

<div class="mb-4">
    <h1 class="text-2xl font-bold text-gray-900"><?= e($account['account_name']) ?></h1>
    <p class="text-sm text-gray-500 capitalize"><?= e($account['type']) ?> account<?= !empty($account['account_number_masked']) ? ' · ' . e($account['account_number_masked']) : '' ?></p>
</div>

<div class="grid gap-4 sm:grid-cols-4">
    <div class="card card-body"><p class="text-sm text-gray-500">Opening</p><p class="mt-1 text-xl font-bold text-gray-900">₹ <?= money($ledger['opening']) ?></p></div>
    <div class="card card-body"><p class="text-sm text-gray-500">Total in</p><p class="mt-1 text-xl font-bold text-brand-700">₹ <?= money($ledger['total_in']) ?></p></div>
    <div class="card card-body"><p class="text-sm text-gray-500">Total out</p><p class="mt-1 text-xl font-bold text-red-600">₹ <?= money($ledger['total_out']) ?></p></div>
    <div class="card card-body"><p class="text-sm text-gray-500">Balance</p><p class="mt-1 text-xl font-bold <?= $ledger['balance'] < 0 ? 'text-red-600' : 'text-brand-700' ?>">₹ <?= money($ledger['balance']) ?></p></div>
</div>

<div class="mt-4 card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="table">
            <thead><tr><th>Date</th><th>Type</th><th>Details</th><th class="text-right">In</th><th class="text-right">Out</th><th class="text-right">Balance</th></tr></thead>
            <tbody>
            <tr class="bg-gray-50">
                <td colspan="5" class="font-medium text-gray-500">Opening balance</td>
                <td class="text-right font-medium">₹ <?= money($ledger['opening']) ?></td>
            </tr>
            <?php foreach ($ledger['rows'] as $row): ?>
                <tr>
                    <td><?= e(format_date($row['date'])) ?></td>
                    <td>
                        <span class="badge <?= $row['kind'] === 'receipt' ? 'bg-brand-100 text-brand-800' : 'bg-red-100 text-red-800' ?> capitalize"><?= e($row['kind']) ?></span>
                    </td>
                    <td class="text-gray-600"><?= e($row['remarks'] ?? ($row['category'] ?? '—')) ?></td>
                    <td class="text-right"><?= $row['in'] > 0 ? '₹ ' . money($row['in']) : '—' ?></td>
                    <td class="text-right"><?= $row['out'] > 0 ? '₹ ' . money($row['out']) : '—' ?></td>
                    <td class="text-right font-medium">₹ <?= money($row['running']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($ledger['rows'] === []): ?>
                <tr><td colspan="6" class="text-center text-gray-400 py-6">No transactions yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
