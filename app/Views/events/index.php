<?php $this->layout('layouts.app');
/** @var list $events */ /** @var array $paginator */ /** @var string $search */
$filterQs = http_build_query(array_filter(['q' => $search]));
$statusBadge = static fn (string $s): string => [
    'planned'   => 'bg-sky-100 text-sky-800',
    'completed' => 'bg-brand-100 text-brand-800',
    'cancelled' => 'bg-gray-100 text-gray-600',
][$s] ?? 'bg-gray-100 text-gray-600';
?>

<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Events</h1>
        <p class="mt-1 text-sm text-gray-500">Association events with type, dates and venue.</p>
    </div>
    <a href="<?= e(url('/events/create')) ?>" class="btn-primary">+ Add Event</a>
</div>

<form method="get" action="<?= e(url('/events')) ?>" class="card card-body mb-6 flex flex-wrap items-end gap-2">
    <div class="grow">
        <label for="q" class="form-label">Search</label>
        <input type="text" id="q" name="q" value="<?= e($search) ?>" placeholder="Title or venue…" class="form-input w-full">
    </div>
    <button type="submit" class="btn-secondary">Search</button>
    <?php if ($search !== ''): ?><a href="<?= e(url('/events')) ?>" class="btn-secondary">Clear</a><?php endif; ?>
</form>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="table">
            <thead><tr><th>Title</th><th>Type</th><th>Venue</th><th>Dates</th><th>Status</th><th class="text-right">Value</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
            <?php foreach ($events as $ev): ?>
                <tr>
                    <td class="font-medium text-gray-900"><a href="<?= e(url('/events/' . $ev['id'])) ?>" class="text-brand-700 hover:underline"><?= e($ev['title']) ?></a></td>
                    <td><?= e($ev['event_type_name'] ?? '—') ?></td>
                    <td><?= e($ev['venue'] ?? '—') ?></td>
                    <td class="text-sm text-gray-600">
                        <?php if ($ev['start_date']): ?>
                            <?= e(format_date($ev['start_date'])) ?><?= $ev['end_date'] ? ' – ' . e(format_date($ev['end_date'])) : '' ?>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td><span class="badge capitalize <?= $statusBadge($ev['status']) ?>"><?= e($ev['status']) ?></span></td>
                    <td class="text-right font-medium">₹ <?= money($ev['value']) ?></td>
                    <td class="whitespace-nowrap text-right">
                        <a href="<?= e(url('/events/' . $ev['id'] . '/edit')) ?>" class="text-brand-700 hover:underline">Edit</a>
                        <span class="text-gray-300">·</span>
                        <form method="post" action="<?= e(url('/events/' . $ev['id'] . '/delete')) ?>" class="inline" data-confirm="Delete this event?">
                            <?= csrf_field() ?>
                            <button type="submit" class="text-red-600 hover:underline">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($events === []): ?>
                <tr><td colspan="7" class="text-center text-gray-400 py-8">No events yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="p-4"><?php $baseUrl = url('/events' . ($filterQs ? '?' . $filterQs : '')); include dirname(__DIR__) . '/partials/pagination.php'; ?></div>
</div>
