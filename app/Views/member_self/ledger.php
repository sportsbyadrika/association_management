<?php $this->layout('layouts.app'); /** @var array $member */ /** @var array $ledger */ ?>

<h1 class="mb-1 text-2xl font-bold text-gray-900">My Ledger</h1>
<p class="mb-6 text-sm text-gray-500">Your demands, receipts and running balance.</p>

<?php include dirname(__DIR__) . '/partials/ledger_table.php'; ?>
