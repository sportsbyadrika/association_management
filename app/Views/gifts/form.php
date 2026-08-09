<?php $this->layout('layouts.app');
/** @var array|null $gift */ /** @var list $types */ /** @var list $members */ /** @var list $giftMembers */
$g = $gift ?? null;
$giftMembers = $giftMembers ?? [];
$isEdit = $g !== null;
$action = $isEdit ? url('/gifts/' . $g['id']) : url('/gifts');
$val = static fn (string $k, $d = '') => e(old($k) !== '' ? old($k) : ($g[$k] ?? $d));
$dDirection = (string) (old('direction') !== '' ? old('direction') : ($g['direction'] ?? 'in'));
$selType = static fn ($id) => (string) (old('gift_type_id') !== '' ? old('gift_type_id') : ($g['gift_type_id'] ?? '')) === (string) $id ? 'selected' : '';
?>

<h1 class="mb-6 text-2xl font-bold text-gray-900"><?= $isEdit ? 'Edit' : 'Record' ?> Gift</h1>

<div class="max-w-3xl card card-body">
    <form method="post" action="<?= e($action) ?>" class="space-y-5" novalidate>
        <?= csrf_field() ?>
        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="direction" class="form-label">Direction *</label>
                <select id="direction" name="direction" class="form-select">
                    <option value="in" <?= $dDirection === 'in' ? 'selected' : '' ?>>Received (donation in)</option>
                    <option value="out" <?= $dDirection === 'out' ? 'selected' : '' ?>>Given (gift out)</option>
                </select>
                <?php if ($m = error_for('direction')): ?><p class="form-error"><?= e($m) ?></p><?php endif; ?>
            </div>
            <div>
                <label for="gift_type_id" class="form-label">Gift type</label>
                <select id="gift_type_id" name="gift_type_id" class="form-select">
                    <option value="">— Select —</option>
                    <?php foreach ($types as $t): ?>
                        <option value="<?= (int) $t['id'] ?>" <?= $selType($t['id']) ?>><?= e($t['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if ($m = error_for('gift_type_id')): ?><p class="form-error"><?= e($m) ?></p><?php endif; ?>
            </div>
            <div class="sm:col-span-2">
                <label for="title" class="form-label">Title / item *</label>
                <input type="text" id="title" name="title" value="<?= $val('title') ?>" required autofocus class="form-input" placeholder="e.g. Annual day trophy, Corpus donation">
                <?php if ($m = error_for('title')): ?><p class="form-error"><?= e($m) ?></p><?php endif; ?>
            </div>
            <div>
                <label for="party" class="form-label">Donor / Recipient</label>
                <input type="text" id="party" name="party" value="<?= $val('party') ?>" class="form-input" placeholder="Name of donor or recipient">
            </div>
            <div>
                <label for="gift_date" class="form-label">Date</label>
                <input type="date" id="gift_date" name="gift_date" value="<?= $val('gift_date', date('Y-m-d')) ?>" class="form-input">
                <?php if ($m = error_for('gift_date')): ?><p class="form-error"><?= e($m) ?></p><?php endif; ?>
            </div>
            <div>
                <label for="default_contribution" class="form-label">Default per-person amount (₹)</label>
                <input type="number" step="0.01" min="0" id="default_contribution" name="default_contribution" value="<?= $val('default_contribution') ?>" class="form-input" placeholder="Pre-fills each added member">
                <?php if ($m = error_for('default_contribution')): ?><p class="form-error"><?= e($m) ?></p><?php endif; ?>
            </div>
            <div>
                <label for="value" class="form-label">Total value (₹)</label>
                <input type="number" step="0.01" min="0" id="value" name="value" value="<?= $val('value') ?>" class="form-input">
                <p class="mt-1 text-xs text-gray-400">Auto-set to the sum of member contributions when members are added.</p>
                <?php if ($m = error_for('value')): ?><p class="form-error"><?= e($m) ?></p><?php endif; ?>
            </div>
            <div class="sm:col-span-2">
                <label for="description" class="form-label">Description</label>
                <textarea id="description" name="description" rows="2" class="form-textarea"><?= $val('description') ?></textarea>
            </div>
        </div>

        <!-- Related members with per-person contributions -->
        <div class="border-t border-gray-100 pt-5" data-member-contrib="gift_member">
            <div class="mb-2 flex items-center justify-between">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Related members &amp; contributions</h2>
                <p class="text-sm text-gray-500">Total: ₹ <span data-gm-total class="font-semibold text-gray-900">0.00</span></p>
            </div>
            <p class="mb-3 text-xs text-gray-400">Search a member, click Add — the contribution pre-fills with the default per-person amount and can be changed per member.</p>

            <div class="flex flex-wrap items-end gap-2">
                <div class="grow">
                    <label for="gm_search" class="form-label">Add member</label>
                    <input type="text" id="gm_search" data-gm-search list="gmOptions" class="form-input w-full" placeholder="Type a name or member no…" autocomplete="off">
                    <datalist id="gmOptions">
                        <?php foreach ($members as $mem): ?>
                            <option data-id="<?= (int) $mem['id'] ?>" data-name="<?= e($mem['name']) ?>" value="<?= e($mem['name']) ?> (#<?= (int) $mem['id'] ?>)"></option>
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <button type="button" data-gm-add class="btn-secondary">Add</button>
            </div>

            <div class="mt-3 overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Member</th><th class="w-48">Contribution (₹)</th><th class="w-24 text-right">Action</th></tr></thead>
                    <tbody data-gm-rows>
                    <?php foreach ($giftMembers as $gm): ?>
                        <tr>
                            <td>
                                <?= e($gm['name']) ?><?= $gm['member_number'] ? ' (' . e($gm['member_number']) . ')' : '' ?>
                                <input type="hidden" name="gift_member_ids[]" value="<?= (int) $gm['member_id'] ?>">
                            </td>
                            <td><input type="number" step="0.01" min="0" name="gift_member_contributions[]" value="<?= e(number_format((float) $gm['contribution'], 2, '.', '')) ?>" class="form-input"></td>
                            <td class="text-right"><button type="button" data-gm-remove class="text-red-600 hover:underline">Remove</button></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <p data-gm-empty class="py-3 text-center text-sm text-gray-400 <?= $giftMembers === [] ? '' : 'hidden' ?>">No members added yet.</p>
            </div>
        </div>

        <div class="flex gap-2 border-t border-gray-100 pt-4">
            <button type="submit" class="btn-primary"><?= $isEdit ? 'Save changes' : 'Save gift' ?></button>
            <a href="<?= e(url('/gifts')) ?>" class="btn-secondary">Cancel</a>
        </div>
    </form>
</div>
