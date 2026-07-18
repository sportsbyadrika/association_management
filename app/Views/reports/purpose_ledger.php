<?php $this->layout('layouts.app');
/** @var list $purposes */ /** @var int $purposeId */ /** @var array|null $selectedPurpose */
/** @var list $financialYears */ /** @var array|null $selectedFy */ /** @var mixed $fyParam */
/** @var list $rows */ /** @var array $totals */
$fyValue = $fyParam !== null && $fyParam !== '' ? (string) $fyParam : (string) ($selectedFy['id'] ?? '');
$qs = 'purpose_id=' . $purposeId . '&fy=' . urlencode($fyValue);
?>

<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <a href="<?= e(url('/reports')) ?>" class="text-sm text-gray-500 hover:text-brand-700">&larr; Reports</a>
        <h1 class="mt-1 text-2xl font-bold text-gray-900">Purpose Ledger</h1>
        <p class="mt-1 text-sm text-gray-500">Project-wise demand, collection and balance for a demand purpose.</p>
    </div>
    <div class="flex gap-2">
        <a href="<?= e(url('/reports/purpose-ledger?' . $qs . '&format=csv')) ?>" class="btn-secondary btn-sm">CSV</a>
        <a href="<?= e(url('/reports/purpose-ledger?' . $qs . '&format=pdf')) ?>" class="btn-primary btn-sm">PDF</a>
    </div>
</div>

<form method="get" action="<?= e(url('/reports/purpose-ledger')) ?>" class="card card-body mb-6 flex flex-wrap items-end gap-3">
    <div>
        <label for="purpose_id" class="form-label">Purpose</label>
        <select id="purpose_id" name="purpose_id" class="form-select">
            <?php foreach ($purposes as $p): ?>
                <option value="<?= (int) $p['id'] ?>" <?= (int) $p['id'] === $purposeId ? 'selected' : '' ?>>
                    <?= e($p['name']) ?> (<?= e($p['type']) ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label for="fy" class="form-label">Financial year</label>
        <select id="fy" name="fy" class="form-select">
            <?php if ($financialYears === []): ?>
                <option value="all">All</option>
            <?php else: ?>
                <?php foreach ($financialYears as $fy): ?>
                    <option value="<?= (int) $fy['id'] ?>" <?= $fyValue === (string) $fy['id'] ? 'selected' : '' ?>><?= e($fy['label']) ?></option>
                <?php endforeach; ?>
                <option value="all" <?= (string) $fyParam === 'all' ? 'selected' : '' ?>>All years</option>
            <?php endif; ?>
        </select>
    </div>
    <button type="submit" class="btn-primary">Apply</button>
</form>

<div class="mb-4 grid gap-4 sm:grid-cols-3">
    <div class="card card-body"><p class="text-sm text-gray-500">Total demand</p><p class="mt-1 text-xl font-bold text-gray-900">₹ <?= money($totals['demand']) ?></p></div>
    <div class="card card-body"><p class="text-sm text-gray-500">Collected</p><p class="mt-1 text-xl font-bold text-brand-700">₹ <?= money($totals['collected']) ?></p></div>
    <div class="card card-body"><p class="text-sm text-gray-500">Balance</p><p class="mt-1 text-xl font-bold <?= $totals['balance'] > 0 ? 'text-amber-600' : 'text-brand-700' ?>">₹ <?= money($totals['balance']) ?></p></div>
</div>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="table">
            <thead><tr>
                <th>Sl No.</th><th>Project</th>
                <th class="text-right">Members</th><th class="text-right">Total Demand</th>
                <th class="text-right">Collections</th><th class="text-right">Collected</th>
                <th class="text-right">Pending</th><th class="text-right">Balance</th>
                <th class="text-right">List</th>
            </tr></thead>
            <tbody>
            <?php foreach ($rows as $i => $r): ?>
                <?php $projectVal = $r['project_id'] !== null ? (string) (int) $r['project_id'] : 'none'; ?>
                <tr>
                    <td class="text-gray-400"><?= $i + 1 ?></td>
                    <td class="font-medium text-gray-700"><?= e($r['project_name'] ?: 'No project') ?></td>
                    <td class="text-right"><?= (int) $r['members_demanded'] ?></td>
                    <td class="text-right">₹ <?= money($r['total_demand']) ?></td>
                    <td class="text-right"><?= (int) $r['collections_count'] ?></td>
                    <td class="text-right text-brand-700">₹ <?= money($r['collected']) ?></td>
                    <td class="text-right"><?= (int) $r['balance_count'] ?></td>
                    <td class="text-right <?= (float) $r['balance'] > 0 ? 'text-amber-600' : 'text-gray-400' ?>">₹ <?= money($r['balance']) ?></td>
                    <td class="text-right">
                        <a href="<?= e(url('/reports/purpose-ledger?' . $qs . '&project=' . $projectVal)) ?>"
                           title="View member-wise list"
                           class="inline-flex items-center justify-center rounded-md p-1.5 text-brand-700 hover:bg-brand-50">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01" />
                            </svg>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($rows === []): ?>
                <tr><td colspan="9" class="text-center text-gray-400 py-8">No demands for this purpose<?= $selectedFy ? ' in ' . e($selectedFy['label']) : '' ?>.</td></tr>
            <?php endif; ?>
            </tbody>
            <?php if ($rows !== []): ?>
            <tfoot>
                <tr class="bg-gray-50 font-semibold">
                    <td colspan="2" class="text-right">Total</td>
                    <td class="text-right"><?= (int) $totals['members'] ?></td>
                    <td class="text-right">₹ <?= money($totals['demand']) ?></td>
                    <td class="text-right"><?= (int) $totals['collections'] ?></td>
                    <td class="text-right">₹ <?= money($totals['collected']) ?></td>
                    <td class="text-right"><?= (int) $totals['balance_count'] ?></td>
                    <td class="text-right">₹ <?= money($totals['balance']) ?></td>
                    <td></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>
