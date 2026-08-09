<?php $this->layout('layouts.app'); /** @var array $gift */ /** @var list $giftMembers */
$isIn = $gift['direction'] === 'in';
$giftMembers = $giftMembers ?? [];
?>

<div class="mb-6 flex items-center justify-between">
    <a href="<?= e(url('/gifts')) ?>" class="text-sm text-gray-500 hover:text-brand-700">&larr; Back to gifts</a>
    <a href="<?= e(url('/gifts/' . $gift['id'] . '/edit')) ?>" class="btn-primary">Edit gift</a>
</div>

<div class="max-w-2xl card card-body">
    <div class="flex items-start justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900"><?= e($gift['title']) ?></h1>
            <span class="badge mt-2 <?= $isIn ? 'bg-brand-100 text-brand-800' : 'bg-amber-100 text-amber-800' ?>">
                <?= $isIn ? 'Donation received (in)' : 'Gift given (out)' ?>
            </span>
        </div>
        <p class="text-2xl font-bold text-gray-900">₹ <?= money($gift['value']) ?></p>
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

<?php if ($giftMembers !== []): ?>
<div class="mt-6 max-w-2xl card overflow-hidden">
    <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
        <h2 class="font-semibold text-gray-900">Related members &amp; contributions</h2>
        <span class="text-sm font-semibold text-gray-900">₹ <?= money(array_sum(array_map(static fn ($m) => (float) $m['contribution'], $giftMembers))) ?></span>
    </div>
    <div class="overflow-x-auto">
        <table class="table">
            <thead><tr><th>Member</th><th class="text-right">Contribution</th></tr></thead>
            <tbody>
            <?php foreach ($giftMembers as $gm): ?>
                <tr>
                    <td><?= e($gm['name']) ?><?= $gm['member_number'] ? ' <span class="text-gray-400">(' . e($gm['member_number']) . ')</span>' : '' ?></td>
                    <td class="text-right">₹ <?= money($gm['contribution']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
