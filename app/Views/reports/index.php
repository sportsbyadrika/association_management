<?php $this->layout('layouts.app'); ?>

<h1 class="mb-1 text-2xl font-bold text-gray-900">Reports</h1>
<p class="mb-6 text-sm text-gray-500">Download as CSV or PDF. All reports are scoped to your association.</p>

<div class="grid gap-4 sm:grid-cols-2">
    <div class="card card-body">
        <h2 class="font-semibold text-gray-900">Members directory</h2>
        <p class="mt-1 text-sm text-gray-500">All members with key details. Choose a PDF layout.</p>
        <form method="get" action="<?= e(url('/reports/members')) ?>" class="mt-4 flex flex-wrap items-end gap-2">
            <input type="hidden" name="format" value="pdf">
            <div>
                <label for="layout" class="form-label">PDF layout</label>
                <select id="layout" name="layout" class="form-select">
                    <option value="list">List wise</option>
                    <option value="card">Card wise (ID cards)</option>
                </select>
            </div>
            <button type="submit" class="btn-primary btn-sm">Download PDF</button>
            <a href="<?= e(url('/reports/members?format=csv')) ?>" class="btn-secondary btn-sm">Download CSV</a>
        </form>
    </div>

    <div class="card card-body">
        <h2 class="font-semibold text-gray-900">Member wise Ledger</h2>
        <p class="mt-1 text-sm text-gray-500">Per-member dues, receipts and balance.</p>
        <p class="mt-3 text-xs text-gray-400">Open any member and use the ledger page's CSV/PDF buttons.</p>
        <a href="<?= e(url('/members')) ?>" class="btn-secondary btn-sm mt-3">Go to members</a>
    </div>

    <div class="card card-body">
        <h2 class="font-semibold text-gray-900">Ledger (Subscription, Projects, Gifts)</h2>
        <p class="mt-1 text-sm text-gray-500">Per-member dues, collection &amp; balance for a purpose (e.g. Subscription), with a financial-year filter.</p>
        <a href="<?= e(url('/reports/purpose-ledger')) ?>" class="btn-primary btn-sm mt-4">Open</a>
    </div>

    <div class="card card-body">
        <h2 class="font-semibold text-gray-900">Income report</h2>
        <p class="mt-1 text-sm text-gray-500">Receipts by income head and by project, with a date filter.</p>
        <a href="<?= e(url('/reports/income')) ?>" class="btn-primary btn-sm mt-4">Open</a>
    </div>

    <div class="card card-body">
        <h2 class="font-semibold text-gray-900">Expenditure report</h2>
        <p class="mt-1 text-sm text-gray-500">Expenditure by category and by project, with a date filter.</p>
        <a href="<?= e(url('/reports/expenditure')) ?>" class="btn-primary btn-sm mt-4">Open</a>
    </div>

    <div class="card card-body">
        <h2 class="font-semibold text-gray-900">Income &amp; Expenditure</h2>
        <p class="mt-1 text-sm text-gray-500">Project-wise income and expenditure with net and a grand total, with a date filter.</p>
        <a href="<?= e(url('/reports/income-expenditure')) ?>" class="btn-primary btn-sm mt-4">Open</a>
    </div>
</div>
