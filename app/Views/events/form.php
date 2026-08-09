<?php $this->layout('layouts.app');
/** @var array|null $event */ /** @var list $types */ /** @var list $members */ /** @var list $eventMembers */
$ev = $event ?? null;
$members = $members ?? [];
$eventMembers = $eventMembers ?? [];
$isEdit = $ev !== null;
$action = $isEdit ? url('/events/' . $ev['id']) : url('/events');
$val = static fn (string $k, $d = '') => e(old($k) !== '' ? old($k) : ($ev[$k] ?? $d));
$selType = static fn ($id) => (string) (old('event_type_id') !== '' ? old('event_type_id') : ($ev['event_type_id'] ?? '')) === (string) $id ? 'selected' : '';
$dStatus = (string) (old('status') !== '' ? old('status') : ($ev['status'] ?? 'planned'));
?>

<h1 class="mb-6 text-2xl font-bold text-gray-900"><?= $isEdit ? 'Edit' : 'Add' ?> Event</h1>

<div class="max-w-3xl card card-body">
    <form method="post" action="<?= e($action) ?>" class="space-y-5" novalidate>
        <?= csrf_field() ?>
        <div class="grid gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="title" class="form-label">Title *</label>
                <input type="text" id="title" name="title" value="<?= $val('title') ?>" required autofocus class="form-input">
                <?php if ($m = error_for('title')): ?><p class="form-error"><?= e($m) ?></p><?php endif; ?>
            </div>
            <div>
                <label for="event_type_id" class="form-label">Event type</label>
                <select id="event_type_id" name="event_type_id" class="form-select">
                    <option value="">— Select —</option>
                    <?php foreach ($types as $t): ?>
                        <option value="<?= (int) $t['id'] ?>" <?= $selType($t['id']) ?>><?= e($t['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if ($m = error_for('event_type_id')): ?><p class="form-error"><?= e($m) ?></p><?php endif; ?>
            </div>
            <div>
                <label for="status" class="form-label">Status *</label>
                <select id="status" name="status" class="form-select">
                    <?php foreach (['planned' => 'Planned', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $k => $lbl): ?>
                        <option value="<?= $k ?>" <?= $dStatus === $k ? 'selected' : '' ?>><?= $lbl ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="venue" class="form-label">Venue</label>
                <input type="text" id="venue" name="venue" value="<?= $val('venue') ?>" class="form-input" placeholder="Hall / ground name">
            </div>
            <div>
                <label for="location" class="form-label">Location</label>
                <input type="text" id="location" name="location" value="<?= $val('location') ?>" class="form-input" placeholder="Address / city">
            </div>
            <div>
                <label for="start_date" class="form-label">Start date</label>
                <input type="date" id="start_date" name="start_date" value="<?= $val('start_date') ?>" class="form-input">
                <?php if ($m = error_for('start_date')): ?><p class="form-error"><?= e($m) ?></p><?php endif; ?>
            </div>
            <div>
                <label for="end_date" class="form-label">End date</label>
                <input type="date" id="end_date" name="end_date" value="<?= $val('end_date') ?>" class="form-input">
                <?php if ($m = error_for('end_date')): ?><p class="form-error"><?= e($m) ?></p><?php endif; ?>
            </div>
            <div>
                <label for="registration_start" class="form-label">Registration start</label>
                <input type="date" id="registration_start" name="registration_start" value="<?= $val('registration_start') ?>" class="form-input">
                <?php if ($m = error_for('registration_start')): ?><p class="form-error"><?= e($m) ?></p><?php endif; ?>
            </div>
            <div>
                <label for="registration_end" class="form-label">Registration close</label>
                <input type="date" id="registration_end" name="registration_end" value="<?= $val('registration_end') ?>" class="form-input">
                <?php if ($m = error_for('registration_end')): ?><p class="form-error"><?= e($m) ?></p><?php endif; ?>
            </div>
            <div>
                <label for="default_contribution" class="form-label">Default per-person amount (₹)</label>
                <input type="number" step="0.01" min="0" id="default_contribution" name="default_contribution" value="<?= $val('default_contribution') ?>" class="form-input" placeholder="Pre-fills each added member">
                <?php if ($m = error_for('default_contribution')): ?><p class="form-error"><?= e($m) ?></p><?php endif; ?>
            </div>
            <div>
                <label for="value" class="form-label">Budget / value (₹)</label>
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
        <div class="border-t border-gray-100 pt-5" data-member-contrib="event_member">
            <div class="mb-2 flex items-center justify-between">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Related members &amp; contributions</h2>
                <p class="text-sm text-gray-500">Total: ₹ <span data-gm-total class="font-semibold text-gray-900">0.00</span></p>
            </div>
            <p class="mb-3 text-xs text-gray-400">Search a member, click Add — the contribution pre-fills with the default per-person amount and can be changed per member.</p>

            <div class="flex flex-wrap items-end gap-2">
                <div class="grow">
                    <label for="em_search" class="form-label">Add member</label>
                    <input type="text" id="em_search" data-gm-search list="emOptions" class="form-input w-full" placeholder="Type a name or member no…" autocomplete="off">
                    <datalist id="emOptions">
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
                    <?php foreach ($eventMembers as $em): ?>
                        <tr>
                            <td>
                                <?= e($em['name']) ?><?= $em['member_number'] ? ' (' . e($em['member_number']) . ')' : '' ?>
                                <input type="hidden" name="event_member_ids[]" value="<?= (int) $em['member_id'] ?>">
                            </td>
                            <td><input type="number" step="0.01" min="0" name="event_member_contributions[]" value="<?= e(number_format((float) $em['contribution'], 2, '.', '')) ?>" class="form-input"></td>
                            <td class="text-right"><button type="button" data-gm-remove class="text-red-600 hover:underline">Remove</button></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <p data-gm-empty class="py-3 text-center text-sm text-gray-400 <?= $eventMembers === [] ? '' : 'hidden' ?>">No members added yet.</p>
            </div>
        </div>

        <div class="flex gap-2 border-t border-gray-100 pt-4">
            <button type="submit" class="btn-primary"><?= $isEdit ? 'Save changes' : 'Save event' ?></button>
            <a href="<?= e(url('/events')) ?>" class="btn-secondary">Cancel</a>
        </div>
    </form>
</div>
