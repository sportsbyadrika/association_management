<?php $this->layout('layouts.app'); /** @var list $accounts */ ?>

<h1 class="mb-6 text-2xl font-bold text-gray-900">Masters</h1>
<?php include dirname(__DIR__) . '/partials/masters_tabs.php'; ?>

<div class="mb-6 flex items-center justify-between">
    <div>
        <h2 class="text-lg font-semibold text-gray-900">Bank Accounts</h2>
        <p class="mt-1 text-sm text-gray-500">Association and treasurer accounts with live balances.</p>
    </div>
    <a href="<?= e(url('/bank-accounts/create')) ?>" class="btn-primary">+ Add Account</a>
</div>

<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
    <?php foreach ($accounts as $a): ?>
        <a href="<?= e(url('/bank-accounts/' . $a['id'])) ?>" class="card card-body block transition hover:shadow-md">
            <div class="flex items-start justify-between">
                <h2 class="font-semibold text-gray-900"><?= e($a['account_name']) ?></h2>
                <span class="badge <?= $a['type'] === 'treasurer' ? 'bg-indigo-100 text-indigo-800' : 'bg-brand-100 text-brand-800' ?> capitalize"><?= e($a['type']) ?></span>
            </div>
            <?php if (!empty($a['account_number_masked'])): ?>
                <p class="mt-1 text-xs text-gray-400"><?= e($a['account_number_masked']) ?></p>
            <?php endif; ?>
            <p class="mt-4 text-sm text-gray-500">Current balance</p>
            <p class="text-2xl font-bold <?= $a['balance'] < 0 ? 'text-red-600' : 'text-brand-700' ?>">₹ <?= money($a['balance']) ?></p>
        </a>
    <?php endforeach; ?>
    <?php if ($accounts === []): ?>
        <div class="col-span-full card card-body text-center text-gray-400 py-10">No bank accounts yet.</div>
    <?php endif; ?>
</div>
