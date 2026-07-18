<?php $this->layout('layouts.app'); /** @var list $expenditures */ /** @var array $paginator */ ?>

<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Expenditure</h1>
        <p class="mt-1 text-sm text-gray-500">Money spent by the association.</p>
    </div>
    <a href="<?= e(url('/expenditures/create')) ?>" class="btn-primary">+ Record Expenditure</a>
</div>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="table">
            <thead><tr><th>Date</th><th>Head</th><th>Category</th><th>Project</th><th>Mode</th><th>Bank</th><th>Remarks</th><th class="text-right">Amount</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
            <?php foreach ($expenditures as $x): ?>
                <tr>
                    <td><?= e(format_date($x['paid_on'])) ?></td>
                    <td class="font-medium text-gray-900"><?= e($x['head_name'] ?? '—') ?></td>
                    <td class="capitalize"><?= e($x['category']) ?></td>
                    <td><?= e($x['project_name'] ?? '—') ?></td>
                    <td class="capitalize"><?= e(str_replace('_', ' ', $x['mode'])) ?></td>
                    <td><?= e($x['bank_name'] ?? '—') ?></td>
                    <td class="max-w-xs truncate text-gray-600" title="<?= e($x['remarks'] ?? '') ?>"><?= e($x['remarks'] ?: '—') ?></td>
                    <td class="text-right font-medium text-red-600">₹ <?= money($x['amount']) ?></td>
                    <td class="whitespace-nowrap text-right">
                        <a href="<?= e(url('/expenditures/' . $x['id'] . '/edit')) ?>" class="text-brand-700 hover:underline">Edit</a>
                        <span class="text-gray-300">·</span>
                        <form method="post" action="<?= e(url('/expenditures/' . $x['id'] . '/delete')) ?>" class="inline" data-confirm="Delete this expenditure?">
                            <?= csrf_field() ?>
                            <button type="submit" class="text-red-600 hover:underline">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($expenditures === []): ?>
                <tr><td colspan="9" class="text-center text-gray-400 py-8">No expenditures yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="p-4"><?php $baseUrl = url('/expenditures'); include dirname(__DIR__) . '/partials/pagination.php'; ?></div>
</div>
