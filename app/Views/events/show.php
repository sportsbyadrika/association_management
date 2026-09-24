<?php $this->layout('layouts.app'); /** @var array $event */ /** @var float $spent */ /** @var float $collected */ /** @var list $eventMembers */
/** @var list $collections */ /** @var list $expenditures */
$eventMembers = $eventMembers ?? [];
$collections = $collections ?? [];
$expenditures = $expenditures ?? [];
$memberTotal = array_sum(array_map(static fn ($m) => (float) $m['contribution'], $eventMembers));
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
        <a href="<?= e(url('/demands/create?event_id=' . $event['id'])) ?>" class="btn-secondary btn-sm">Raise due</a>
        <a href="<?= e(url('/expenditures/create?category=event&event_id=' . $event['id'])) ?>" class="btn-secondary btn-sm">Add expenditure</a>
        <a href="<?= e(url('/activities/event/' . $event['id'] . '/move')) ?>" class="btn-secondary btn-sm">Move…</a>
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

<!-- Contributions / Collected / Expenditure tabs -->
<div class="mt-6 max-w-3xl">
    <div class="flex flex-wrap gap-1 border-b border-gray-200">
        <button type="button" data-etab="contributions"
            class="-mb-px border-b-2 border-brand-600 px-4 py-2 text-sm font-medium text-brand-700">
            Contributions / Budget <span class="ml-1 rounded-full bg-brand-50 px-2 py-0.5 text-xs text-brand-700">₹ <?= money($memberTotal) ?></span>
        </button>
        <button type="button" data-etab="collected"
            class="-mb-px border-b-2 border-transparent px-4 py-2 text-sm font-medium text-gray-500 hover:text-gray-700">
            Collected <span class="ml-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">₹ <?= money($collected) ?></span>
        </button>
        <button type="button" data-etab="expenditure"
            class="-mb-px border-b-2 border-transparent px-4 py-2 text-sm font-medium text-gray-500 hover:text-gray-700">
            Expenditure <span class="ml-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">₹ <?= money($spent) ?></span>
        </button>
    </div>

    <!-- Contributions / Budget panel -->
    <div data-epanel="contributions" class="mt-4">
        <div class="card overflow-hidden">
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                <h2 class="font-semibold text-gray-900">Related members &amp; contributions</h2>
                <span class="text-sm font-semibold text-gray-900">₹ <?= money($memberTotal) ?></span>
            </div>
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th class="w-16">Sl No.</th><th>Member</th><th class="text-right">Contribution</th></tr></thead>
                    <tbody>
                    <?php foreach ($eventMembers as $i => $em): ?>
                        <tr>
                            <td class="text-gray-400"><?= $i + 1 ?></td>
                            <td><?= e($em['name']) ?><?= $em['member_number'] ? ' <span class="text-gray-400">(' . e($em['member_number']) . ')</span>' : '' ?></td>
                            <td class="text-right">₹ <?= money($em['contribution']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($eventMembers === []): ?>
                        <tr><td colspan="3" class="text-center text-gray-400 py-6">No member contributions recorded.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Collected (receipts) panel -->
    <div data-epanel="collected" class="mt-4 hidden">
        <div class="card overflow-hidden">
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                <div class="flex items-center gap-3">
                    <h2 class="font-semibold text-gray-900">Collections</h2>
                    <a href="<?= e(url('/receipts/create?category=event&event_id=' . $event['id'])) ?>" class="text-sm text-brand-700 hover:underline">+ Add</a>
                </div>
                <span class="text-sm font-semibold text-brand-700">₹ <?= money($collected) ?></span>
            </div>
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th class="w-16">Sl No.</th><th>Date</th><th>Income Head</th><th>Received From</th><th>Mode</th><th>Remarks</th><th class="text-right">Amount</th></tr></thead>
                    <tbody>
                    <?php foreach ($collections as $i => $r): ?>
                        <tr>
                            <td class="text-gray-400"><?= $i + 1 ?></td>
                            <td><?= $r['received_on'] ? e(format_date($r['received_on'])) : '—' ?></td>
                            <td><?= e($r['income_head_name'] ?: '—') ?></td>
                            <td><?= e($r['member_name'] ?: '—') ?></td>
                            <td class="capitalize"><?= e(str_replace('_', ' ', $r['mode'])) ?></td>
                            <td class="max-w-xs truncate text-gray-600" title="<?= e($r['remarks'] ?? '') ?>"><?= e($r['remarks'] ?: '—') ?></td>
                            <td class="text-right font-medium text-brand-700">₹ <?= money($r['amount']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($collections === []): ?>
                        <tr><td colspan="7" class="text-center text-gray-400 py-6">No collections recorded yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Expenditure panel -->
    <div data-epanel="expenditure" class="mt-4 hidden">
        <div class="card overflow-hidden">
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                <div class="flex items-center gap-3">
                    <h2 class="font-semibold text-gray-900">Expenditure</h2>
                    <a href="<?= e(url('/expenditures/create?category=event&event_id=' . $event['id'])) ?>" class="text-sm text-brand-700 hover:underline">+ Add</a>
                </div>
                <span class="text-sm font-semibold text-red-600">₹ <?= money($spent) ?></span>
            </div>
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th class="w-16">Sl No.</th><th>Date</th><th>Head</th><th>Category</th><th>Mode</th><th>Remarks</th><th class="text-right">Amount</th></tr></thead>
                    <tbody>
                    <?php foreach ($expenditures as $i => $r): ?>
                        <tr>
                            <td class="text-gray-400"><?= $i + 1 ?></td>
                            <td><?= $r['paid_on'] ? e(format_date($r['paid_on'])) : '—' ?></td>
                            <td><?= e($r['head_name'] ?: '—') ?></td>
                            <td class="capitalize"><?= e($r['category']) ?></td>
                            <td class="capitalize"><?= e(str_replace('_', ' ', $r['mode'])) ?></td>
                            <td class="max-w-xs truncate text-gray-600" title="<?= e($r['remarks'] ?? '') ?>"><?= e($r['remarks'] ?: '—') ?></td>
                            <td class="text-right font-medium text-red-600">₹ <?= money($r['amount']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($expenditures === []): ?>
                        <tr><td colspan="7" class="text-center text-gray-400 py-6">No expenditure recorded yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var tabs = document.querySelectorAll('[data-etab]');
    var panels = document.querySelectorAll('[data-epanel]');
    tabs.forEach(function (t) {
        t.addEventListener('click', function () {
            var name = t.getAttribute('data-etab');
            tabs.forEach(function (x) {
                var on = x === t;
                x.classList.toggle('border-brand-600', on);
                x.classList.toggle('text-brand-700', on);
                x.classList.toggle('border-transparent', !on);
                x.classList.toggle('text-gray-500', !on);
            });
            panels.forEach(function (p) {
                p.classList.toggle('hidden', p.getAttribute('data-epanel') !== name);
            });
        });
    });
})();
</script>
