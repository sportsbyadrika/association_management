<?php
/** @var array $member */ /** @var array $ledger */
/**
 * Full member ledger: three summary cards (Subscription, Activities received,
 * Amount received) that filter the entries table below when clicked.
 */
$summary = $ledger['summary'] ?? [
    'subscription' => ['due' => 0, 'received' => 0, 'outstanding' => 0],
    'received'     => ['subscription' => 0, 'project' => 0, 'gift' => 0, 'event' => 0, 'activities' => 0, 'total' => 0],
];
$sub = $summary['subscription'];
$rec = $summary['received'];
$actAmt = static fn (float $v) => $v > 0 ? '₹ ' . money($v) : '(No receipts)';
?>
<div data-ledger>
    <div class="grid gap-4 lg:grid-cols-3">
        <!-- Card 1: Subscription (click to filter) -->
        <button type="button" data-ledger-filter="subscription"
                class="card card-body block w-full text-left transition hover:shadow-md focus:outline-none">
            <p class="text-sm font-semibold uppercase tracking-wide text-gray-500">Subscription</p>
            <dl class="mt-3 space-y-1.5 text-sm">
                <div class="flex justify-between"><dt class="text-gray-500">Due</dt><dd class="font-medium text-gray-900">₹ <?= money($sub['due']) ?></dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Received</dt><dd class="font-medium text-brand-700">₹ <?= money($sub['received']) ?></dd></div>
                <div class="flex justify-between border-t border-gray-100 pt-1.5"><dt class="text-gray-500">Outstanding</dt><dd class="font-bold <?= $sub['outstanding'] > 0 ? 'text-amber-600' : 'text-gray-900' ?>">₹ <?= money($sub['outstanding']) ?></dd></div>
            </dl>
            <p class="mt-2 text-xs text-brand-600">Click to filter ↓</p>
        </button>

        <!-- Card 2: Activities received (each row filters) -->
        <div class="card card-body">
            <p class="text-sm font-semibold uppercase tracking-wide text-gray-500">Activities</p>
            <div class="mt-3 space-y-1">
                <?php foreach (['project' => 'Projects', 'gift' => 'Gifts', 'event' => 'Events'] as $key => $lbl): ?>
                    <button type="button" data-ledger-filter="<?= $key ?>"
                            class="flex w-full items-center justify-between rounded-md px-2 py-1.5 text-sm hover:bg-brand-50 focus:outline-none">
                        <span class="text-gray-600"><?= $lbl ?></span>
                        <span class="font-medium <?= $rec[$key] > 0 ? 'text-brand-700' : 'text-gray-400' ?>"><?= $actAmt((float) $rec[$key]) ?></span>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Card 3: Amount received (summary) -->
        <div class="card card-body">
            <p class="text-sm font-semibold uppercase tracking-wide text-gray-500">Amount received</p>
            <dl class="mt-3 space-y-1.5 text-sm">
                <div class="flex justify-between"><dt class="text-gray-500">Subscription</dt><dd class="font-medium text-gray-900">₹ <?= money($rec['subscription']) ?></dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Activities</dt><dd class="font-medium text-gray-900">₹ <?= money($rec['activities']) ?></dd></div>
                <div class="flex justify-between border-t border-gray-100 pt-1.5"><dt class="text-gray-500">Total</dt><dd class="font-bold text-brand-700">₹ <?= money($rec['total']) ?></dd></div>
            </dl>
        </div>
    </div>

    <div class="mt-3 flex items-center justify-between">
        <p class="text-sm text-gray-500">Showing: <span data-ledger-label class="font-medium text-gray-800">All entries</span></p>
        <button type="button" data-ledger-filter="all" data-ledger-showall class="hidden text-sm font-medium text-brand-700 hover:underline">Show all</button>
    </div>

    <?php $ledgerCards = false; include __DIR__ . '/ledger_table.php'; ?>
</div>
