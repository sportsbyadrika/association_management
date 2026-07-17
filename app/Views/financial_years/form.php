<?php $this->layout('layouts.app'); /** @var array|null $item */
$isEdit = $item !== null;
$action = $isEdit ? url('/masters/financial-years/' . $item['id']) : url('/masters/financial-years');
$val = static fn (string $k, $d = '') => e(old($k) !== '' ? old($k) : ($item[$k] ?? $d));
?>

<h1 class="mb-6 text-2xl font-bold text-gray-900"><?= $isEdit ? 'Edit' : 'Add' ?> Financial Year</h1>

<div class="max-w-xl card card-body">
    <form method="post" action="<?= e($action) ?>" class="space-y-5" novalidate>
        <?= csrf_field() ?>
        <div>
            <label for="label" class="form-label">Label *</label>
            <input type="text" id="label" name="label" value="<?= $val('label') ?>" required placeholder="e.g. FY 2025-26" class="form-input">
            <?php if ($m = error_for('label')): ?><p class="form-error"><?= e($m) ?></p><?php endif; ?>
        </div>
        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="start_date" class="form-label">From date *</label>
                <input type="date" id="start_date" name="start_date" value="<?= $val('start_date') ?>" required class="form-input">
                <?php if ($m = error_for('start_date')): ?><p class="form-error"><?= e($m) ?></p><?php endif; ?>
            </div>
            <div>
                <label for="end_date" class="form-label">To date *</label>
                <input type="date" id="end_date" name="end_date" value="<?= $val('end_date') ?>" required class="form-input">
                <?php if ($m = error_for('end_date')): ?><p class="form-error"><?= e($m) ?></p><?php endif; ?>
            </div>
        </div>
        <label class="inline-flex items-center gap-2">
            <input type="checkbox" name="is_active" value="1" <?= (old('is_active') !== '' ? old('is_active') : ($item['is_active'] ?? 1)) ? 'checked' : '' ?> class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
            <span class="text-sm text-gray-700">Active</span>
        </label>
        <div class="flex gap-2 border-t border-gray-100 pt-4">
            <button type="submit" class="btn-primary"><?= $isEdit ? 'Save changes' : 'Create' ?></button>
            <a href="<?= e(url('/masters/financial-years')) ?>" class="btn-secondary">Cancel</a>
        </div>
    </form>
</div>
