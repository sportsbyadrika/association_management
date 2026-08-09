<?php $this->layout('layouts.app'); /** @var array $event */ /** @var float $spent */ /** @var float $collected */ /** @var list $eventMembers */
$eventMembers = $eventMembers ?? [];
$statusBadge = [
    'planned'   => 'bg-sky-100 text-sky-800',
    'completed' => 'bg-brand-100 text-brand-800',
    'cancelled' => 'bg-gray-100 text-gray-600',
][$event['status']] ?? 'bg-gray-100 text-gray-600';
?>

<div class="mb-6 flex items-center justify-between">
    <a href="<?= e(url('/events')) ?>" class="text-sm text-gray-500 hover:text-brand-700">&larr; Back to events</a>
    <div class="flex flex-wrap gap-2">
        <a href="<?= e(url('/receipts/create?category=event&event_id=' . $event['id'])) ?>" class="btn-secondary btn-sm">Add collection</a>
        <a href="<?= e(url('/expenditures/create?category=event&event_id=' . $event['id'])) ?>" class="btn-secondary btn-sm">Add expenditure</a>
        <a href="<?= e(url('/events/' . $event['id'] . '/edit')) ?>" class="btn-primary btn-sm">Edit</a>
    </div>
</div>

<div class="max-w-3xl card card-body">
    <div class="flex items-start justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900"><?= e($event['title']) ?></h1>
            <p class="text-sm text-gray-500"><?= e($event['event_type_name'] ?? 'Event') ?></p>
        </div>
        <span class="badge capitalize <?= $statusBadge ?>"><?= e($event['status']) ?></span>
    </div>

    <div class="mt-4 grid grid-cols-2 gap-4 text-sm sm:grid-cols-3">
        <div><p class="text-gray-500">Budget / value</p><p class="font-semibold">₹ <?= money($event['value']) ?></p></div>
        <div><p class="text-gray-500">Collected</p><p class="font-semibold text-brand-700">₹ <?= money($collected) ?></p></div>
        <div><p class="text-gray-500">Spent</p><p class="font-semibold text-red-600">₹ <?= money($spent) ?></p></div>
    </div>

    <dl class="mt-6 space-y-3 text-sm">
        <?php
        $fields = [
            'Venue'              => $event['venue'] ?? '—',
            'Location'           => $event['location'] ?? '—',
            'Start date'         => $event['start_date'] ? format_date($event['start_date']) : '—',
            'End date'           => $event['end_date'] ? format_date($event['end_date']) : '—',
            'Registration start' => $event['registration_start'] ? format_date($event['registration_start']) : '—',
            'Registration close' => $event['registration_end'] ? format_date($event['registration_end']) : '—',
        ];
        foreach ($fields as $label => $value): ?>
            <div class="flex justify-between gap-4">
                <dt class="text-gray-500"><?= e($label) ?></dt>
                <dd class="text-right font-medium text-gray-900"><?= e($value) ?></dd>
            </div>
        <?php endforeach; ?>
        <?php if (!empty($event['description'])): ?>
            <div><dt class="text-gray-500">Description</dt><dd class="mt-1 text-gray-900"><?= nl2br(e($event['description'])) ?></dd></div>
        <?php endif; ?>
    </dl>
</div>

<?php if ($eventMembers !== []): ?>
<div class="mt-6 max-w-3xl card overflow-hidden">
    <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
        <h2 class="font-semibold text-gray-900">Related members &amp; contributions</h2>
        <span class="text-sm font-semibold text-gray-900">₹ <?= money(array_sum(array_map(static fn ($m) => (float) $m['contribution'], $eventMembers))) ?></span>
    </div>
    <div class="overflow-x-auto">
        <table class="table">
            <thead><tr><th>Member</th><th class="text-right">Contribution</th></tr></thead>
            <tbody>
            <?php foreach ($eventMembers as $em): ?>
                <tr>
                    <td><?= e($em['name']) ?><?= $em['member_number'] ? ' <span class="text-gray-400">(' . e($em['member_number']) . ')</span>' : '' ?></td>
                    <td class="text-right">₹ <?= money($em['contribution']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
