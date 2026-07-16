<?php $this->layout('layouts.app'); /** @var array $member */ ?>

<h1 class="mb-6 text-2xl font-bold text-gray-900">My Profile</h1>

<div class="max-w-3xl card card-body">
    <div class="flex flex-col items-center gap-6 sm:flex-row sm:items-start">
        <div class="flex-shrink-0">
            <?php if (!empty($member['photo_path'])): ?>
                <img src="<?= e(url('/photo/member/' . $member['id'])) ?>" alt="" class="h-28 w-28 rounded-full object-cover ring-2 ring-brand-100">
            <?php else: ?>
                <span class="inline-flex h-28 w-28 items-center justify-center rounded-full bg-brand-100 text-3xl font-bold text-brand-700"><?= e(strtoupper(substr($member['name'], 0, 1))) ?></span>
            <?php endif; ?>
        </div>
        <div class="w-full">
            <h2 class="text-xl font-bold text-gray-900"><?= e($member['name']) ?></h2>
            <p class="text-sm text-gray-500"><?= e($member['member_type_name'] ?? 'Member') ?></p>
            <dl class="mt-4 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                <?php
                $fields = [
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
                    <div class="flex justify-between border-b border-gray-100 pb-2">
                        <dt class="text-gray-500"><?= e($label) ?></dt>
                        <dd class="font-medium text-gray-900"><?= e($value) ?></dd>
                    </div>
                <?php endforeach; ?>
            </dl>
            <?php if (!empty($member['address'])): ?>
                <div class="mt-3 text-sm"><span class="text-gray-500">Address:</span> <?= e($member['address']) ?></div>
            <?php endif; ?>
        </div>
    </div>
    <p class="mt-6 rounded-lg bg-gray-50 px-4 py-3 text-xs text-gray-500">This information is maintained by your association. To update it, please contact your association administrator.</p>
</div>
