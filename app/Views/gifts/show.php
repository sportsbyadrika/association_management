<?php $this->layout('layouts.app');
/** @var array $gift */ /** @var list $giftMembers */
/** @var list $collections */ /** @var list $expenditures */ /** @var float $collected */ /** @var float $spent */
$isIn = $gift['direction'] === 'in';
$giftMembers = $giftMembers ?? [];
$collections = $collections ?? [];
$expenditures = $expenditures ?? [];
$collected = $collected ?? 0;
$spent = $spent ?? 0;
$memberTotal = array_sum(array_map(static fn ($m) => (float) $m['contribution'], $giftMembers));
?>

<div class="mb-6 flex items-center justify-between">
    <a href="<?= e(url('/gifts')) ?>" class="text-sm text-gray-500 hover:text-brand-700">&larr; Back to gifts</a>
    <div class="flex flex-wrap gap-2">
        <button type="button" data-form-modal="<?= e(url('/receipts/create?category=gift&gift_id=' . $gift['id'] . '&embed=1')) ?>" data-form-modal-title="Add collection" class="btn-secondary btn-sm">Add collection</button>
        <button type="button" data-form-modal="<?= e(url('/expenditures/create?category=gift&gift_id=' . $gift['id'] . '&embed=1')) ?>" data-form-modal-title="Add expenditure" class="btn-secondary btn-sm">Add expenditure</button>
        <button type="button" data-form-modal="<?= e(url('/demands/create?gift_id=' . $gift['id'] . '&embed=1')) ?>" data-form-modal-title="Raise due" class="btn-secondary btn-sm">Raise due</button>
        <a href="<?= e(url('/activities/gift/' . $gift['id'] . '/move')) ?>" class="btn-secondary btn-sm">Move…</a>
        <a href="<?= e(url('/gifts/' . $gift['id'] . '/edit')) ?>" class="btn-primary btn-sm">Edit gift</a>
    </div>
</div>

<div class="max-w-2xl card card-body">
    <div class="flex items-start justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900"><?= e($gift['title']) ?></h1>
            <span class="badge mt-2 <?= $isIn ? 'bg-brand-100 text-brand-800' : 'bg-amber-100 text-amber-800' ?>">
                <?= $isIn ? 'Donation received (in)' : 'Gift given (out)' ?>
            </span>
        </div>
    </div>

    <div class="mt-4 grid grid-cols-2 gap-4 text-sm sm:grid-cols-3">
        <div><p class="text-gray-500">Value</p><p class="font-semibold">₹ <?= money($gift['value']) ?></p></div>
        <div><p class="text-gray-500">Collected</p><p class="font-semibold text-brand-700">₹ <?= money($collected) ?></p></div>
        <div><p class="text-gray-500">Spent</p><p class="font-semibold text-red-600">₹ <?= money($spent) ?></p></div>
    </div>

    <dl class="mt-6 space-y-3 text-sm">
        <?php
        $fields = [
            'Gift type'          => $gift['gift_type_name'] ?? '—',
            ($isIn ? 'Donor' : 'Recipient') => $gift['party'] ?? '—',
            'Related member'     => $gift['member_name'] ?? '—',
            'Date'               => $gift['gift_date'] ? format_date($gift['gift_date']) : '—',
        ];
        foreach ($fields as $label => $value): ?>
            <div class="flex justify-between gap-4">
                <dt class="text-gray-500"><?= e($label) ?></dt>
                <dd class="text-right font-medium text-gray-900"><?= e($value) ?></dd>
            </div>
        <?php endforeach; ?>
        <?php if (!empty($gift['description'])): ?>
            <div><dt class="text-gray-500">Description</dt><dd class="mt-1 text-gray-900"><?= nl2br(e($gift['description'])) ?></dd></div>
        <?php endif; ?>
    </dl>
</div>

<!-- Contributions / Expenditure tabs -->
<div class="mt-6 max-w-3xl">
    <div class="flex gap-1 border-b border-gray-200">
        <button type="button" data-gtab="contributions"
            class="-mb-px border-b-2 border-brand-600 px-4 py-2 text-sm font-medium text-brand-700">
            Contributions <span class="ml-1 rounded-full bg-brand-50 px-2 py-0.5 text-xs text-brand-700">₹ <?= money($collected) ?></span>
        </button>
        <button type="button" data-gtab="expenditure"
            class="-mb-px border-b-2 border-transparent px-4 py-2 text-sm font-medium text-gray-500 hover:text-gray-700">
            Expenditure <span class="ml-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">₹ <?= money($spent) ?></span>
        </button>
    </div>

    <!-- Contributions panel: actual collections (receipts) only -->
    <div data-gpanel="contributions" class="mt-4 space-y-6">

        <div class="card overflow-hidden">
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                <div class="flex items-center gap-3">
                    <h2 class="font-semibold text-gray-900">Collections</h2>
                    <button type="button" data-form-modal="<?= e(url('/receipts/create?category=gift&gift_id=' . $gift['id'] . '&embed=1')) ?>" data-form-modal-title="Add collection" class="text-sm text-brand-700 hover:underline">+ Add</button>
                </div>
                <span class="text-sm font-semibold text-brand-700">₹ <?= money($collected) ?></span>
            </div>
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Date</th><th>Income Head</th><th>Received From</th><th>Mode</th><th>Remarks</th><th class="text-right">Amount</th><th class="text-right">Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($collections as $r): ?>
                        <tr>
                            <td><?= $r['received_on'] ? e(format_date($r['received_on'])) : '—' ?></td>
                            <td><?= e($r['income_head_name'] ?: '—') ?></td>
                            <td><?= e($r['member_name'] ?: '—') ?></td>
                            <td class="capitalize"><?= e(str_replace('_', ' ', $r['mode'])) ?></td>
                            <td class="max-w-xs truncate text-gray-600" title="<?= e($r['remarks'] ?? '') ?>"><?= e($r['remarks'] ?: '—') ?></td>
                            <td class="text-right font-medium text-brand-700">₹ <?= money($r['amount']) ?></td>
                            <td class="whitespace-nowrap text-right">
                                <button type="button" data-form-modal="<?= e(url('/receipts/' . $r['id'] . '/edit?embed=1')) ?>" data-form-modal-title="Edit collection" class="text-brand-700 hover:underline">Edit</button>
                                <span class="text-gray-300">·</span>
                                <form method="post" action="<?= e(url('/receipts/' . $r['id'] . '/delete')) ?>" class="inline" data-confirm="Delete this receipt?">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                </form>
                            </td>
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
    <div data-gpanel="expenditure" class="mt-4 hidden">
        <div class="card overflow-hidden">
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                <div class="flex items-center gap-3">
                    <h2 class="font-semibold text-gray-900">Expenditure</h2>
                    <button type="button" data-form-modal="<?= e(url('/expenditures/create?category=gift&gift_id=' . $gift['id'] . '&embed=1')) ?>" data-form-modal-title="Add expenditure" class="text-sm text-brand-700 hover:underline">+ Add</button>
                </div>
                <span class="text-sm font-semibold text-red-600">₹ <?= money($spent) ?></span>
            </div>
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Date</th><th>Head</th><th>Category</th><th>Mode</th><th>Remarks</th><th class="text-right">Amount</th><th class="text-right">Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($expenditures as $r): ?>
                        <tr>
                            <td><?= $r['paid_on'] ? e(format_date($r['paid_on'])) : '—' ?></td>
                            <td><?= e($r['head_name'] ?: '—') ?></td>
                            <td class="capitalize"><?= e($r['category']) ?></td>
                            <td class="capitalize"><?= e(str_replace('_', ' ', $r['mode'])) ?></td>
                            <td class="max-w-xs truncate text-gray-600" title="<?= e($r['remarks'] ?? '') ?>"><?= e($r['remarks'] ?: '—') ?></td>
                            <td class="text-right font-medium text-red-600">₹ <?= money($r['amount']) ?></td>
                            <td class="whitespace-nowrap text-right">
                                <button type="button" data-form-modal="<?= e(url('/expenditures/' . $r['id'] . '/edit?embed=1')) ?>" data-form-modal-title="Edit expenditure" class="text-brand-700 hover:underline">Edit</button>
                                <span class="text-gray-300">·</span>
                                <form method="post" action="<?= e(url('/expenditures/' . $r['id'] . '/delete')) ?>" class="inline" data-confirm="Delete this expenditure?">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                </form>
                            </td>
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
    var tabs = document.querySelectorAll('[data-gtab]');
    var panels = document.querySelectorAll('[data-gpanel]');
    tabs.forEach(function (t) {
        t.addEventListener('click', function () {
            var name = t.getAttribute('data-gtab');
            tabs.forEach(function (x) {
                var on = x === t;
                x.classList.toggle('border-brand-600', on);
                x.classList.toggle('text-brand-700', on);
                x.classList.toggle('border-transparent', !on);
                x.classList.toggle('text-gray-500', !on);
            });
            panels.forEach(function (p) {
                p.classList.toggle('hidden', p.getAttribute('data-gpanel') !== name);
            });
        });
    });
})();
</script>

<?php include dirname(__DIR__) . '/partials/form_modal.php'; ?>
