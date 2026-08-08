<?php $this->layout('layouts.app');
/** @var array|null $gift */ /** @var list $types */ /** @var list $members */
$g = $gift ?? null;
$isEdit = $g !== null;
$action = $isEdit ? url('/gifts/' . $g['id']) : url('/gifts');
$val = static fn (string $k, $d = '') => e(old($k) !== '' ? old($k) : ($g[$k] ?? $d));
$dDirection = (string) (old('direction') !== '' ? old('direction') : ($g['direction'] ?? 'in'));
$selType = static fn ($id) => (string) (old('gift_type_id') !== '' ? old('gift_type_id') : ($g['gift_type_id'] ?? '')) === (string) $id ? 'selected' : '';
$selMember = static fn ($id) => (string) (old('member_id') !== '' ? old('member_id') : ($g['member_id'] ?? '')) === (string) $id ? 'selected' : '';
?>

<h1 class="mb-6 text-2xl font-bold text-gray-900"><?= $isEdit ? 'Edit' : 'Record' ?> Gift</h1>

<div class="max-w-2xl card card-body">
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
                <label for="member_id" class="form-label">Related member (optional)</label>
                <select id="member_id" name="member_id" class="form-select">
                    <option value="">— None —</option>
                    <?php foreach ($members as $mem): ?>
                        <option value="<?= (int) $mem['id'] ?>" <?= $selMember($mem['id']) ?>><?= e($mem['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if ($m = error_for('member_id')): ?><p class="form-error"><?= e($m) ?></p><?php endif; ?>
            </div>
            <div>
                <label for="value" class="form-label">Value (₹)</label>
                <input type="number" step="0.01" min="0" id="value" name="value" value="<?= $val('value') ?>" class="form-input">
                <?php if ($m = error_for('value')): ?><p class="form-error"><?= e($m) ?></p><?php endif; ?>
            </div>
            <div>
                <label for="gift_date" class="form-label">Date</label>
                <input type="date" id="gift_date" name="gift_date" value="<?= $val('gift_date', date('Y-m-d')) ?>" class="form-input">
                <?php if ($m = error_for('gift_date')): ?><p class="form-error"><?= e($m) ?></p><?php endif; ?>
            </div>
            <div class="sm:col-span-2">
                <label for="description" class="form-label">Description</label>
                <textarea id="description" name="description" rows="2" class="form-textarea"><?= $val('description') ?></textarea>
            </div>
        </div>
        <div class="flex gap-2 border-t border-gray-100 pt-4">
            <button type="submit" class="btn-primary"><?= $isEdit ? 'Save changes' : 'Save gift' ?></button>
            <a href="<?= e(url('/gifts')) ?>" class="btn-secondary">Cancel</a>
        </div>
    </form>
</div>
