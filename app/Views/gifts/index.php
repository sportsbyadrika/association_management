<?php $this->layout('layouts.app');
/** @var list $gifts */ /** @var array $paginator */ /** @var array $totals */
/** @var string $direction */ /** @var string $search */
$hasFilter = $direction !== '' || $search !== '';
$filterQs = http_build_query(array_filter(['direction' => $direction, 'q' => $search]));
$dirBadge = static fn (string $d): string => $d === 'in'
    ? 'bg-brand-100 text-brand-800'
    : 'bg-amber-100 text-amber-800';
?>

<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Gifts</h1>
        <p class="mt-1 text-sm text-gray-500">Donations received (in) and gifts given (out).</p>
    </div>
    <a href="<?= e(url('/gifts/create')) ?>" class="btn-primary">+ Record Gift</a>
</div>

<div class="mb-6 grid gap-4 sm:grid-cols-2">
    <div class="card card-body"><p class="text-sm text-gray-500">Donations received (in)</p><p class="mt-1 text-xl font-bold text-brand-700">₹ <?= money($totals['in']) ?></p></div>
    <div class="card card-body"><p class="text-sm text-gray-500">Gifts given (out)</p><p class="mt-1 text-xl font-bold text-amber-600">₹ <?= money($totals['out']) ?></p></div>
</div>

<form method="get" action="<?= e(url('/gifts')) ?>" class="card card-body mb-6 grid grid-cols-1 gap-3 sm:grid-cols-4 sm:items-end">
    <div class="sm:col-span-2">
        <label for="q" class="form-label">Search</label>
        <input type="text" id="q" name="q" value="<?= e($search) ?>" placeholder="Title, donor/recipient or member…" class="form-input w-full">
    </div>
    <div>
        <label for="direction" class="form-label">Direction</label>
        <select id="direction" name="direction" class="form-select w-full">
            <option value="">All</option>
            <option value="in" <?= $direction === 'in' ? 'selected' : '' ?>>Received (in)</option>
            <option value="out" <?= $direction === 'out' ? 'selected' : '' ?>>Given (out)</option>
        </select>
    </div>
    <div class="flex gap-2">
        <button type="submit" class="btn-secondary">Filter</button>
        <?php if ($hasFilter): ?><a href="<?= e(url('/gifts')) ?>" class="btn-secondary">Clear</a><?php endif; ?>
    </div>
</form>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="table">
            <thead><tr><th>Date</th><th>Direction</th><th>Title</th><th>Type</th><th>Donor / Recipient</th><th>Member</th><th class="text-right">Value</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
            <?php foreach ($gifts as $g): ?>
                <tr>
                    <td><?= $g['gift_date'] ? e(format_date($g['gift_date'])) : '—' ?></td>
                    <td><span class="badge <?= $dirBadge($g['direction']) ?>"><?= $g['direction'] === 'in' ? 'Received' : 'Given' ?></span></td>
                    <td class="font-medium text-gray-900"><a href="<?= e(url('/gifts/' . $g['id'])) ?>" class="text-brand-700 hover:underline"><?= e($g['title']) ?></a></td>
                    <td><?= e($g['gift_type_name'] ?? '—') ?></td>
                    <td><?= e($g['party'] ?? '—') ?></td>
                    <td><?= e($g['member_name'] ?? '—') ?></td>
                    <td class="text-right font-medium">₹ <?= money($g['value']) ?></td>
                    <td class="whitespace-nowrap text-right">
                        <a href="<?= e(url('/gifts/' . $g['id'] . '/edit')) ?>" class="text-brand-700 hover:underline">Edit</a>
                        <span class="text-gray-300">·</span>
                        <form method="post" action="<?= e(url('/gifts/' . $g['id'] . '/delete')) ?>" class="inline" data-confirm="Delete this gift?">
                            <?= csrf_field() ?>
                            <button type="submit" class="text-red-600 hover:underline">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($gifts === []): ?>
                <tr><td colspan="8" class="text-center text-gray-400 py-8">No gifts recorded<?= $hasFilter ? ' for this filter' : '' ?>.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="p-4"><?php $baseUrl = url('/gifts' . ($filterQs ? '?' . $filterQs : '')); include dirname(__DIR__) . '/partials/pagination.php'; ?></div>
</div>
