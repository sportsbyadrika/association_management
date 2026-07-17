<?php $this->layout('layouts.app');
/** @var list $demands */ /** @var array $paginator */ /** @var list $financialYears */
/** @var array|null $selectedFy */ /** @var string $search */ /** @var mixed $fyParam */
$currentFyValue = $fyParam !== null && $fyParam !== '' ? (string) $fyParam : (string) ($selectedFy['id'] ?? '');
?>

<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Demands</h1>
        <p class="mt-1 text-sm text-gray-500">Charges raised against members.</p>
    </div>
    <a href="<?= e(url('/demands/create')) ?>" class="btn-primary">+ Raise Demand</a>
</div>

<div class="card">
    <div class="border-b border-gray-100 p-4">
        <form method="get" action="<?= e(url('/demands')) ?>" class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[16rem]">
                <label for="q" class="form-label">Search</label>
                <input type="text" id="q" name="q" value="<?= e($search) ?>" placeholder="Member no, name or mobile…" class="form-input">
            </div>
            <div>
                <label for="fy" class="form-label">Financial year</label>
                <select id="fy" name="fy" class="form-select">
                    <?php if ($financialYears === []): ?>
                        <option value="all">All</option>
                    <?php else: ?>
                        <?php foreach ($financialYears as $fy): ?>
                            <option value="<?= (int) $fy['id'] ?>" <?= $currentFyValue === (string) $fy['id'] ? 'selected' : '' ?>>
                                <?= e($fy['label']) ?>
                            </option>
                        <?php endforeach; ?>
                        <option value="all" <?= (string) $fyParam === 'all' ? 'selected' : '' ?>>All years</option>
                    <?php endif; ?>
                </select>
            </div>
            <button type="submit" class="btn-primary">Filter</button>
            <a href="<?= e(url('/demands')) ?>" class="btn-secondary">Reset</a>
        </form>
        <?php if ($financialYears === []): ?>
            <p class="mt-2 text-xs text-amber-600">Tip: define financial years under Masters → Financial Year to filter demands by year.</p>
        <?php endif; ?>
    </div>

    <div class="overflow-x-auto">
        <table class="table">
            <thead><tr><th>Member No.</th><th>Member</th><th>Mobile</th><th>Purpose</th><th>Project</th><th>Due</th><th class="text-right">Amount</th><th>Status</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
            <?php foreach ($demands as $d): ?>
                <tr>
                    <td class="text-gray-700"><?= e($d['member_number'] ?? '—') ?></td>
                    <td class="font-medium text-gray-900"><?= e($d['member_name']) ?></td>
                    <td><?= e($d['mobile'] ?? '—') ?></td>
                    <td><?= e($d['purpose_name'] ?? '—') ?></td>
                    <td><?= e($d['project_name'] ?? '—') ?></td>
                    <td><?= e(format_date($d['due_date'])) ?></td>
                    <td class="text-right font-medium">₹ <?= money($d['amount']) ?></td>
                    <td>
                        <?php
                        $badge = match ($d['status']) {
                            'paid' => 'bg-brand-100 text-brand-800',
                            'partial' => 'bg-blue-100 text-blue-800',
                            'cancelled' => 'bg-gray-100 text-gray-500',
                            default => 'bg-amber-100 text-amber-800',
                        }; ?>
                        <span class="badge <?= $badge ?> capitalize"><?= e($d['status']) ?></span>
                    </td>
                    <td class="text-right">
                        <?php if (in_array($d['status'], ['pending', 'partial'], true)): ?>
                            <form method="post" action="<?= e(url('/demands/' . $d['id'] . '/mark-paid')) ?>" class="inline" data-confirm="Mark this demand as paid without recording a receipt?">
                                <?= csrf_field() ?>
                                <button type="submit" class="text-brand-700 hover:underline">Mark paid</button>
                            </form>
                            <span class="text-gray-300">·</span>
                        <?php elseif ($d['status'] === 'paid' && (float) ($d['receipts_paid'] ?? 0) < (float) $d['amount']): ?>
                            <form method="post" action="<?= e(url('/demands/' . $d['id'] . '/reopen')) ?>" class="inline" data-confirm="Reopen this demand? It was marked paid without a receipt.">
                                <?= csrf_field() ?>
                                <button type="submit" class="text-gray-500 hover:underline">Reopen</button>
                            </form>
                            <span class="text-gray-300">·</span>
                        <?php endif; ?>
                        <?php if ($d['status'] !== 'cancelled'): ?>
                            <form method="post" action="<?= e(url('/demands/' . $d['id'] . '/delete')) ?>" class="inline" data-confirm="Cancel this demand?">
                                <?= csrf_field() ?>
                                <button type="submit" class="text-red-600 hover:underline">Cancel</button>
                            </form>
                        <?php else: ?>
                            <span class="text-gray-300">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($demands === []): ?>
                <tr><td colspan="9" class="text-center text-gray-400 py-8">No demands match your filters.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="p-4">
        <?php
        $fyQ = $fyParam !== null && $fyParam !== '' ? (string) $fyParam : (string) ($selectedFy['id'] ?? '');
        $baseUrl = url('/demands?q=' . urlencode($search) . '&fy=' . urlencode($fyQ));
        include dirname(__DIR__) . '/partials/pagination.php';
        ?>
    </div>
</div>
