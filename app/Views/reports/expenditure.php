<?php $this->layout('layouts.app'); /** @var array $report */
$qs = 'from=' . urlencode((string) $from) . '&to=' . urlencode((string) $to);
?>

<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <a href="<?= e(url('/reports')) ?>" class="text-sm text-gray-500 hover:text-brand-700">&larr; Reports</a>
        <h1 class="mt-1 text-2xl font-bold text-gray-900">Expenditure Report</h1>
    </div>
    <div class="flex gap-2">
        <a href="<?= e(url('/reports/expenditure?' . $qs . '&format=csv')) ?>" class="btn-secondary btn-sm">CSV</a>
        <a href="<?= e(url('/reports/expenditure?' . $qs . '&format=pdf')) ?>" class="btn-primary btn-sm">PDF</a>
    </div>
</div>

<form method="get" action="<?= e(url('/reports/expenditure')) ?>" class="card card-body mb-6 flex flex-wrap items-end gap-3">
    <div>
        <label for="from" class="form-label">From</label>
        <input type="date" id="from" name="from" value="<?= e($from) ?>" class="form-input">
    </div>
    <div>
        <label for="to" class="form-label">To</label>
        <input type="date" id="to" name="to" value="<?= e($to) ?>" class="form-input">
    </div>
    <button type="submit" class="btn-primary">Apply</button>
    <?php if ($from || $to): ?><a href="<?= e(url('/reports/expenditure')) ?>" class="btn-secondary">Reset</a><?php endif; ?>
    <div class="ml-auto text-right">
        <p class="text-sm text-gray-500">Total expenditure</p>
        <p class="text-2xl font-bold text-red-600">₹ <?= money($report['total']) ?></p>
    </div>
</form>

<div class="grid gap-6 lg:grid-cols-2">
    <div class="card overflow-hidden">
        <div class="border-b border-gray-100 px-6 py-4"><h2 class="font-semibold text-gray-900">By category</h2></div>
        <div class="overflow-x-auto"><table class="table">
            <thead><tr><th>Category</th><th class="text-right">Count</th><th class="text-right">Total</th></tr></thead>
            <tbody>
            <?php foreach ($report['by_category'] as $r): ?>
                <tr><td class="capitalize"><?= e($r['category']) ?></td><td class="text-right"><?= (int) $r['count'] ?></td><td class="text-right font-medium">₹ <?= money($r['total']) ?></td></tr>
            <?php endforeach; ?>
            <?php if ($report['by_category'] === []): ?><tr><td colspan="3" class="text-center text-gray-400 py-6">No data.</td></tr><?php endif; ?>
            </tbody>
        </table></div>
    </div>

    <div class="card overflow-hidden">
        <div class="border-b border-gray-100 px-6 py-4"><h2 class="font-semibold text-gray-900">By project</h2></div>
        <div class="overflow-x-auto"><table class="table">
            <thead><tr><th>Project</th><th class="text-right">Count</th><th class="text-right">Total</th></tr></thead>
            <tbody>
            <?php foreach ($report['by_project'] as $r): ?>
                <tr><td><?= e($r['project']) ?></td><td class="text-right"><?= (int) $r['count'] ?></td><td class="text-right font-medium">₹ <?= money($r['total']) ?></td></tr>
            <?php endforeach; ?>
            <?php if ($report['by_project'] === []): ?><tr><td colspan="3" class="text-center text-gray-400 py-6">No data.</td></tr><?php endif; ?>
            </tbody>
        </table></div>
    </div>

    <div class="card overflow-hidden lg:col-span-2">
        <div class="border-b border-gray-100 px-6 py-4"><h2 class="font-semibold text-gray-900">By expenditure head</h2></div>
        <div class="overflow-x-auto"><table class="table">
            <thead><tr><th>Head</th><th class="text-right">Count</th><th class="text-right">Total</th></tr></thead>
            <tbody>
            <?php foreach ($report['by_head'] as $r): ?>
                <tr><td><?= e($r['head']) ?></td><td class="text-right"><?= (int) $r['count'] ?></td><td class="text-right font-medium">₹ <?= money($r['total']) ?></td></tr>
            <?php endforeach; ?>
            <?php if ($report['by_head'] === []): ?><tr><td colspan="3" class="text-center text-gray-400 py-6">No data.</td></tr><?php endif; ?>
            </tbody>
        </table></div>
    </div>
</div>
