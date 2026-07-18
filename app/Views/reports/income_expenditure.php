<?php $this->layout('layouts.app');
/** @var list $rows */ /** @var array $totals */ /** @var ?string $from */ /** @var ?string $to */
$qs = 'from=' . urlencode((string) $from) . '&to=' . urlencode((string) $to);
?>

<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <a href="<?= e(url('/reports')) ?>" class="text-sm text-gray-500 hover:text-brand-700">&larr; Reports</a>
        <h1 class="mt-1 text-2xl font-bold text-gray-900">Income &amp; Expenditure Report</h1>
        <p class="mt-1 text-sm text-gray-500">Project-wise income and expenditure with net balance.</p>
    </div>
    <div class="flex gap-2">
        <a href="<?= e(url('/reports/income-expenditure?' . $qs . '&format=csv')) ?>" class="btn-secondary btn-sm">CSV</a>
        <a href="<?= e(url('/reports/income-expenditure?' . $qs . '&format=pdf')) ?>" class="btn-primary btn-sm">PDF</a>
    </div>
</div>

<form method="get" action="<?= e(url('/reports/income-expenditure')) ?>" class="card card-body mb-6 flex flex-wrap items-end gap-3">
    <div>
        <label for="from" class="form-label">From</label>
        <input type="date" id="from" name="from" value="<?= e($from ?? '') ?>" class="form-input">
    </div>
    <div>
        <label for="to" class="form-label">To</label>
        <input type="date" id="to" name="to" value="<?= e($to ?? '') ?>" class="form-input">
    </div>
    <button type="submit" class="btn-primary">Apply</button>
    <?php if ($from || $to): ?><a href="<?= e(url('/reports/income-expenditure')) ?>" class="btn-secondary">Reset</a><?php endif; ?>
</form>

<div class="mb-4 grid gap-4 sm:grid-cols-3">
    <div class="card card-body"><p class="text-sm text-gray-500">Total income</p><p class="mt-1 text-xl font-bold text-brand-700">₹ <?= money($totals['income']) ?></p></div>
    <div class="card card-body"><p class="text-sm text-gray-500">Total expense</p><p class="mt-1 text-xl font-bold text-red-600">₹ <?= money($totals['expense']) ?></p></div>
    <div class="card card-body"><p class="text-sm text-gray-500">Net</p><p class="mt-1 text-xl font-bold <?= $totals['net'] < 0 ? 'text-red-600' : 'text-gray-900' ?>">₹ <?= money($totals['net']) ?></p></div>
</div>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="table">
            <thead><tr>
                <th>Sl No.</th><th>Project</th>
                <th class="text-right">Income</th><th class="text-right">Expense</th><th class="text-right">Net</th>
            </tr></thead>
            <tbody>
            <?php foreach ($rows as $i => $r): ?>
                <tr>
                    <td class="text-gray-400"><?= $i + 1 ?></td>
                    <td class="font-medium text-gray-700"><?= e($r['project']) ?></td>
                    <td class="text-right text-brand-700">₹ <?= money($r['income']) ?></td>
                    <td class="text-right text-red-600">₹ <?= money($r['expense']) ?></td>
                    <td class="text-right <?= $r['net'] < 0 ? 'text-red-600' : 'text-gray-900' ?>">₹ <?= money($r['net']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($rows === []): ?>
                <tr><td colspan="5" class="text-center text-gray-400 py-8">No income or expenditure recorded<?= $from || $to ? ' in this range' : '' ?>.</td></tr>
            <?php endif; ?>
            </tbody>
            <?php if ($rows !== []): ?>
            <tfoot>
                <tr class="bg-gray-50 font-semibold">
                    <td colspan="2" class="text-right">Grand Total</td>
                    <td class="text-right text-brand-700">₹ <?= money($totals['income']) ?></td>
                    <td class="text-right text-red-600">₹ <?= money($totals['expense']) ?></td>
                    <td class="text-right <?= $totals['net'] < 0 ? 'text-red-600' : 'text-gray-900' ?>">₹ <?= money($totals['net']) ?></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>
