<?php $this->layout('layouts.app');
/** @var list $receipts */ /** @var array $paginator */
/** @var list $projects */ /** @var list $gifts */ /** @var list $events */
/** @var string $search */ /** @var string $category */ /** @var string $activity */ /** @var ?string $from */ /** @var ?string $to */
$hasFilter = $search !== '' || $category !== '' || $activity !== '' || $from || $to;
$filterQs = http_build_query(array_filter([
    'q'        => $search,
    'category' => $category,
    'activity' => $activity,
    'from'     => $from,
    'to'       => $to,
]));
$catOptions = ['general' => 'General / Subscription', 'project' => 'Project', 'gift' => 'Gift', 'event' => 'Event'];
?>

<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Receipts</h1>
        <p class="mt-1 text-sm text-gray-500">Money received from members and activities.</p>
    </div>
    <a href="<?= e(url('/receipts/create')) ?>" class="btn-primary">+ Record Receipt</a>
</div>

<form method="get" action="<?= e(url('/receipts')) ?>" class="card card-body mb-6 grid grid-cols-1 gap-3 sm:grid-cols-6 sm:items-end">
    <div class="sm:col-span-2">
        <label for="q" class="form-label">Member</label>
        <input type="text" id="q" name="q" value="<?= e($search) ?>" placeholder="Member name or number…" class="form-input w-full">
    </div>
    <div>
        <label for="category" class="form-label">Category</label>
        <select id="category" name="category" class="form-select w-full">
            <option value="">All</option>
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
    <div class="flex gap-2 sm:col-span-6">
        <button type="submit" class="btn-secondary">Filter</button>
        <?php if ($hasFilter): ?><a href="<?= e(url('/receipts')) ?>" class="btn-secondary">Clear</a><?php endif; ?>
    </div>
</form>

<?php
// Group the current page's receipts by date (rows already come ordered by
// received_on DESC), so each date is a collapsible summary row.
$byDate = [];
foreach ($receipts as $r) {
    $byDate[(string) $r['received_on']][] = $r;
}
?>
<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="table">
            <thead><tr><th>Date</th><th>Member</th><th>Income Head</th><th>Linked to</th><th>Mode</th><th>Bank</th><th class="text-right">Amount</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
            <?php $g = 0; foreach ($byDate as $date => $items): $g++; $gid = 'd' . $g;
                $dayTotal = array_sum(array_map(static fn ($x) => (float) $x['amount'], $items)); ?>
                <tr data-date-toggle="<?= $gid ?>" class="cursor-pointer bg-gray-50 hover:bg-gray-100">
                    <td class="font-semibold text-gray-900">
                        <span data-caret class="inline-block w-3 text-brand-600">&#9656;</span>
                        <?= e(format_date($date)) ?>
                    </td>
                    <td colspan="5" class="text-sm text-gray-500"><?= count($items) ?> receipt<?= count($items) === 1 ? '' : 's' ?></td>
                    <td class="text-right font-semibold text-brand-700">₹ <?= money($dayTotal) ?></td>
                    <td></td>
                </tr>
                <?php foreach ($items as $r): ?>
                    <tr data-date-group="<?= $gid ?>" class="hidden">
                        <td class="text-gray-300 text-center">&#8226;</td>
                        <td class="font-medium text-gray-900"><?= e($r['member_name'] ?? '—') ?></td>
                        <td><?= e($r['income_head_name'] ?? '—') ?></td>
                        <td><?= e($r['project_name'] ?? $r['gift_name'] ?? $r['event_name'] ?? '—') ?></td>
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
            <?php endforeach; ?>
            <?php if ($receipts === []): ?>
                <tr><td colspan="8" class="text-center text-gray-400 py-8">No receipts found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="p-4"><?php $baseUrl = url('/receipts' . ($filterQs ? '?' . $filterQs : '')); include dirname(__DIR__) . '/partials/pagination.php'; ?></div>
</div>

<script>
(function () {
    document.querySelectorAll('[data-date-toggle]').forEach(function (h) {
        h.addEventListener('click', function () {
            var key = h.getAttribute('data-date-toggle');
            var open = h.classList.toggle('is-open');
            document.querySelectorAll('[data-date-group="' + key + '"]').forEach(function (row) {
                row.classList.toggle('hidden', !open);
            });
            var caret = h.querySelector('[data-caret]');
            if (caret) { caret.innerHTML = open ? '&#9662;' : '&#9656;'; }
        });
    });
})();
</script>
