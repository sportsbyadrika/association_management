<?php $this->layout('layouts.app');
/** @var list $expenditures */ /** @var array $paginator */
/** @var list $projects */ /** @var list $gifts */ /** @var list $events */
/** @var string $category */ /** @var string $activity */ /** @var ?string $from */ /** @var ?string $to */
$hasFilter = $category !== '' || $activity !== '' || $from || $to;
$filterQs = http_build_query(array_filter([
    'category' => $category,
    'activity' => $activity,
    'from'     => $from,
    'to'       => $to,
]));
$catOptions = ['association' => 'Association (General)', 'project' => 'Project', 'gift' => 'Gift', 'event' => 'Event'];
?>

<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Expenditure</h1>
        <p class="mt-1 text-sm text-gray-500">Money spent by the association.</p>
    </div>
    <a href="<?= e(url('/expenditures/create')) ?>" class="btn-primary">+ Record Expenditure</a>
</div>

<form method="get" action="<?= e(url('/expenditures')) ?>" class="card card-body mb-6 grid grid-cols-1 gap-3 sm:grid-cols-5 sm:items-end">
    <div>
        <label for="category" class="form-label">Category</label>
        <select id="category" name="category" class="form-select w-full">
            <option value="">All expenditure</option>
            <?php foreach ($catOptions as $k => $lbl): ?>
                <option value="<?= $k ?>" <?= $category === $k ? 'selected' : '' ?>><?= e($lbl) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label for="activity" class="form-label">Activity</label>
        <select id="activity" name="activity" class="form-select w-full">
            <option value="">All activities</option>
            <?php if ($projects !== []): ?>
                <optgroup label="Projects">
                    <?php foreach ($projects as $p): ?>
                        <option value="project:<?= (int) $p['id'] ?>" <?= $activity === 'project:' . $p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
                    <?php endforeach; ?>
                </optgroup>
            <?php endif; ?>
            <?php if ($gifts !== []): ?>
                <optgroup label="Gifts">
                    <?php foreach ($gifts as $g): ?>
                        <option value="gift:<?= (int) $g['id'] ?>" <?= $activity === 'gift:' . $g['id'] ? 'selected' : '' ?>><?= e($g['title']) ?></option>
                    <?php endforeach; ?>
                </optgroup>
            <?php endif; ?>
            <?php if ($events !== []): ?>
                <optgroup label="Events">
                    <?php foreach ($events as $ev): ?>
                        <option value="event:<?= (int) $ev['id'] ?>" <?= $activity === 'event:' . $ev['id'] ? 'selected' : '' ?>><?= e($ev['title']) ?></option>
                    <?php endforeach; ?>
                </optgroup>
            <?php endif; ?>
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
    <div class="flex gap-2">
        <button type="submit" class="btn-secondary">Filter</button>
        <?php if ($hasFilter): ?><a href="<?= e(url('/expenditures')) ?>" class="btn-secondary">Clear</a><?php endif; ?>
    </div>
</form>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="table">
            <thead><tr><th>Date</th><th>Head</th><th>Category</th><th>Linked to</th><th>Mode</th><th>Bank</th><th class="text-right">Amount</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
            <?php foreach ($expenditures as $x): ?>
                <tr>
                    <td><?= e(format_date($x['paid_on'])) ?></td>
                    <td class="font-medium text-gray-900">
                        <?= e($x['head_name'] ?? '—') ?>
                        <?php if (!empty($x['remarks'])): ?>
                            <span class="ml-1 cursor-help text-gray-400" title="<?= e($x['remarks']) ?>" aria-label="<?= e($x['remarks']) ?>">&#9432;</span>
                        <?php endif; ?>
                    </td>
                    <td class="capitalize"><?= e($x['category']) ?></td>
                    <td><?= e($x['project_name'] ?? $x['gift_name'] ?? $x['event_name'] ?? '—') ?></td>
                    <td class="capitalize"><?= e(str_replace('_', ' ', $x['mode'])) ?></td>
                    <td><?= e($x['bank_name'] ?? '—') ?></td>
                    <td class="text-right font-medium text-red-600">₹ <?= money($x['amount']) ?></td>
                    <td class="whitespace-nowrap text-right">
                        <a href="<?= e(url('/expenditures/' . $x['id'] . '/edit')) ?>" class="text-brand-700 hover:underline">Edit</a>
                        <span class="text-gray-300">·</span>
                        <form method="post" action="<?= e(url('/expenditures/' . $x['id'] . '/delete')) ?>" class="inline" data-confirm="Delete this expenditure?">
                            <?= csrf_field() ?>
                            <button type="submit" class="text-red-600 hover:underline">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($expenditures === []): ?>
                <tr><td colspan="8" class="text-center text-gray-400 py-8">No expenditures yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="p-4"><?php $baseUrl = url('/expenditures' . ($filterQs ? '?' . $filterQs : '')); include dirname(__DIR__) . '/partials/pagination.php'; ?></div>
</div>
