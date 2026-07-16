<?php $this->layout('layouts.app'); /** @var list $receipts */ /** @var array $paginator */ ?>

<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Receipts</h1>
        <p class="mt-1 text-sm text-gray-500">Money received from members and projects.</p>
    </div>
    <a href="<?= e(url('/receipts/create')) ?>" class="btn-primary">+ Record Receipt</a>
</div>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="table">
            <thead><tr><th>Date</th><th>Member</th><th>Income Head</th><th>Project</th><th>Mode</th><th>Bank</th><th class="text-right">Amount</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
            <?php foreach ($receipts as $r): ?>
                <tr>
                    <td><?= e(format_date($r['received_on'])) ?></td>
                    <td class="font-medium text-gray-900"><?= e($r['member_name'] ?? '—') ?></td>
                    <td><?= e($r['income_head_name'] ?? '—') ?></td>
                    <td><?= e($r['project_name'] ?? '—') ?></td>
                    <td class="capitalize"><?= e(str_replace('_', ' ', $r['mode'])) ?></td>
                    <td><?= e($r['bank_name'] ?? '—') ?></td>
                    <td class="text-right font-medium text-brand-700">₹ <?= money($r['amount']) ?></td>
                    <td class="text-right">
                        <form method="post" action="<?= e(url('/receipts/' . $r['id'] . '/delete')) ?>" class="inline" data-confirm="Delete this receipt?">
                            <?= csrf_field() ?>
                            <button type="submit" class="text-red-600 hover:underline">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($receipts === []): ?>
                <tr><td colspan="8" class="text-center text-gray-400 py-8">No receipts yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="p-4"><?php $baseUrl = url('/receipts'); include dirname(__DIR__) . '/partials/pagination.php'; ?></div>
</div>
