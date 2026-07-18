<?php $this->layout('layouts.app');
/** @var list $receipts */ /** @var array $paginator */
/** @var list $projects */ /** @var string $search */ /** @var string $projectFilter */ /** @var ?string $from */ /** @var ?string $to */
$hasFilter = $search !== '' || $projectFilter !== '' || $from || $to;
$filterQs = http_build_query(array_filter([
    'q'          => $search,
    'project_id' => $projectFilter,
    'from'       => $from,
    'to'         => $to,
]));
?>

<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Receipts</h1>
        <p class="mt-1 text-sm text-gray-500">Money received from members and projects.</p>
    </div>
    <a href="<?= e(url('/receipts/create')) ?>" class="btn-primary">+ Record Receipt</a>
</div>

<form method="get" action="<?= e(url('/receipts')) ?>" class="card card-body mb-6 grid grid-cols-1 gap-3 sm:grid-cols-5 sm:items-end">
    <div class="sm:col-span-2">
        <label for="q" class="form-label">Member</label>
        <input type="text" id="q" name="q" value="<?= e($search) ?>" placeholder="Member name or number…" class="form-input w-full">
    </div>
    <div>
        <label for="project_id" class="form-label">Project</label>
        <select id="project_id" name="project_id" class="form-select w-full">
            <option value="">All</option>
            <option value="none" <?= $projectFilter === 'none' ? 'selected' : '' ?>>General / subscription</option>
            <?php foreach ($projects as $p): ?>
                <option value="<?= (int) $p['id'] ?>" <?= $projectFilter === (string) $p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label for="from" class="form-label">From</label>
        <input type="date" id="from" name="from" value="<?= e($from ?? '') ?>" class="form-input w-full">
    </div>
    <div>
        <label for="to" class="form-label">To</label>
        <input type="date" id="to" name="to" value="<?= e($to ?? '') ?>" class="form-input w-full">
    </div>
    <div class="flex gap-2 sm:col-span-5">
        <button type="submit" class="btn-secondary">Filter</button>
        <?php if ($hasFilter): ?><a href="<?= e(url('/receipts')) ?>" class="btn-secondary">Clear</a><?php endif; ?>
    </div>
</form>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="table">
            <thead><tr><th>Date</th><th>Member</th><th>Income Head</th><th>Project</th><th>Mode</th><th>Bank</th><th class="text-right">Amount</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
            <?php foreach ($receipts as $r): ?>
                <tr>
                    <td><?= e(format_date($r['received_on'])) ?></td>
                    <td class="font-medium text-gray-900"><?= e($r['member_name'] ?? '—') ?></td>
                    <td><?= e($r['income_head_name'] ?? '—') ?></td>
                    <td><?= e($r['project_name'] ?? '—') ?></td>
                    <td class="capitalize"><?= e(str_replace('_', ' ', $r['mode'])) ?></td>
                    <td><?= e($r['bank_name'] ?? '—') ?></td>
                    <td class="text-right font-medium text-brand-700">₹ <?= money($r['amount']) ?></td>
                    <td class="whitespace-nowrap text-right">
                        <a href="<?= e(url('/receipts/' . $r['id'] . '/edit')) ?>" class="text-brand-700 hover:underline">Edit</a>
                        <span class="text-gray-300">·</span>
                        <form method="post" action="<?= e(url('/receipts/' . $r['id'] . '/delete')) ?>" class="inline" data-confirm="Delete this receipt?">
                            <?= csrf_field() ?>
                            <button type="submit" class="text-red-600 hover:underline">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($receipts === []): ?>
                <tr><td colspan="8" class="text-center text-gray-400 py-8">No receipts found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="p-4"><?php $baseUrl = url('/receipts' . ($filterQs ? '?' . $filterQs : '')); include dirname(__DIR__) . '/partials/pagination.php'; ?></div>
</div>
