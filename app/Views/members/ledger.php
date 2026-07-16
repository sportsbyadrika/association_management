<?php $this->layout('layouts.app'); /** @var array $member */ /** @var array $ledger */ ?>

<div class="mb-6 flex items-center justify-between">
    <div>
        <a href="<?= e(url('/members/' . $member['id'])) ?>" class="text-sm text-gray-500 hover:text-brand-700">&larr; <?= e($member['name']) ?></a>
        <h1 class="mt-1 text-2xl font-bold text-gray-900">Member Ledger</h1>
    </div>
    <div class="flex gap-2">
        <a href="<?= e(url('/reports/member-ledger?member_id=' . $member['id'] . '&format=csv')) ?>" class="btn-secondary btn-sm">CSV</a>
        <a href="<?= e(url('/reports/member-ledger?member_id=' . $member['id'] . '&format=pdf')) ?>" class="btn-secondary btn-sm">PDF</a>
    </div>
</div>

<?php include dirname(__DIR__) . '/partials/ledger_table.php'; ?>
