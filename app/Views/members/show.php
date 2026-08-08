<?php $this->layout('layouts.app'); /** @var array $member */ /** @var array $ledger */ /** @var list $familyMembers */
$familyMembers = $familyMembers ?? [];
?>

<div class="mb-6 flex items-center justify-between">
    <a href="<?= e(url('/members')) ?>" class="text-sm text-gray-500 hover:text-brand-700">&larr; Back to members</a>
    <a href="<?= e(url('/members/' . $member['id'] . '/edit')) ?>" class="btn-primary">Edit member</a>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="card card-body">
        <div class="flex flex-col items-center text-center">
            <?php if (!empty($member['photo_path'])): ?>
                <img src="<?= e(url('/photo/member/' . $member['id'])) ?>" alt="" class="h-28 w-28 rounded-full object-cover ring-2 ring-brand-100">
            <?php else: ?>
                <span class="inline-flex h-28 w-28 items-center justify-center rounded-full bg-brand-100 text-3xl font-bold text-brand-700"><?= e(strtoupper(substr($member['name'], 0, 1))) ?></span>
            <?php endif; ?>
            <h1 class="mt-4 text-xl font-bold text-gray-900"><?= e($member['name']) ?></h1>
            <p class="text-sm text-gray-500"><?= e($member['member_type_name'] ?? 'Member') ?></p>
            <?php if ((int) $member['is_active'] !== 1): ?>
                <span class="badge mt-2 bg-gray-100 text-gray-600">Inactive</span>
            <?php endif; ?>
        </div>
        <dl class="mt-6 space-y-3 text-sm">
            <?php
            $fields = [
                'Member No.' => $member['member_number'] ?? '—',
                'Age' => $member['age'] ?? '—',
                'Gender' => ucfirst((string) ($member['gender'] ?? '—')),
                'Mobile' => $member['mobile'] ?? '—',
                'WhatsApp' => $member['whatsapp'] ?? '—',
                'Email' => $member['email'] ?? '—',
                'Family members' => $member['family_members_count'] ?? '—',
                'Occupation' => $member['occupation'] ?? '—',
                'Joined on' => format_date($member['joined_on'] ?? null),
            ];
            foreach ($fields as $label => $value): ?>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500"><?= e($label) ?></dt>
                    <dd class="text-right font-medium text-gray-900"><?= e($value) ?></dd>
                </div>
            <?php endforeach; ?>
            <?php if (!empty($member['address'])): ?>
                <div><dt class="text-gray-500">Address</dt><dd class="mt-1 text-gray-900"><?= e($member['address']) ?></dd></div>
            <?php endif; ?>
            <?php if (!empty($member['notes'])): ?>
                <div><dt class="text-gray-500">Notes</dt><dd class="mt-1 text-gray-900"><?= e($member['notes']) ?></dd></div>
            <?php endif; ?>
        </dl>
    </div>

    <div class="lg:col-span-2">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Ledger</h2>
            <div class="flex gap-2">
                <a href="<?= e(url('/demands/create?member_id=' . $member['id'])) ?>" class="btn-secondary btn-sm">Raise due</a>
                <a href="<?= e(url('/receipts/create?member_id=' . $member['id'])) ?>" class="btn-secondary btn-sm">Record receipt</a>
                <a href="<?= e(url('/members/' . $member['id'] . '/ledger')) ?>" class="btn-secondary btn-sm">Full ledger</a>
            </div>
        </div>
        <?php include dirname(__DIR__) . '/partials/ledger_table.php'; ?>
    </div>
</div>

<div class="mt-8 card">
    <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
        <div>
            <h2 class="font-semibold text-gray-900">Family members</h2>
            <p class="text-xs text-gray-500"><?= count($familyMembers) ?> recorded</p>
        </div>
        <a href="<?= e(url('/members/' . $member['id'] . '/family/create')) ?>" class="btn-primary btn-sm">+ Add Family Member</a>
    </div>
    <div class="overflow-x-auto">
        <table class="table">
            <thead><tr><th>Photo</th><th>Name</th><th>Type</th><th>Relation</th><th>Age</th><th>Gender</th><th>Mobile</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
            <?php foreach ($familyMembers as $f): ?>
                <tr>
                    <td>
                        <?php if (!empty($f['photo_path'])): ?>
                            <img src="<?= e(url('/photo/family/' . $f['id'])) ?>" alt="" class="h-9 w-9 rounded-full object-cover ring-1 ring-gray-200">
                        <?php else: ?>
                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-brand-100 text-sm font-semibold text-brand-700"><?= e(strtoupper(substr($f['name'], 0, 1))) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="font-medium text-gray-900"><?= e($f['name']) ?></td>
                    <td><?= e($f['type_name'] ?? '—') ?></td>
                    <td><?= e($f['relation'] ?? '—') ?></td>
                    <td><?= e($f['age'] ?? '—') ?></td>
                    <td class="capitalize"><?= e($f['gender'] ?? '—') ?></td>
                    <td><?= e($f['mobile'] ?? '—') ?></td>
                    <td class="whitespace-nowrap text-right">
                        <a href="<?= e(url('/family/' . $f['id'] . '/edit')) ?>" class="text-brand-700 hover:underline">Edit</a>
                        <span class="text-gray-300">·</span>
                        <form method="post" action="<?= e(url('/family/' . $f['id'] . '/delete')) ?>" class="inline" data-confirm="Remove this family member?">
                            <?= csrf_field() ?>
                            <button type="submit" class="text-red-600 hover:underline">Remove</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($familyMembers === []): ?>
                <tr><td colspan="8" class="text-center text-gray-400 py-8">No family members recorded yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
