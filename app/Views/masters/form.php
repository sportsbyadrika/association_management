<?php $this->layout('layouts.app'); /** @var array|null $item */ /** @var string $key */
$isEdit = $item !== null;
$action = $isEdit ? url('/masters/' . $key . '/' . $item['id']) : url('/masters/' . $key);
$val = static fn (string $k, $d = '') => e(old($k) !== '' ? old($k) : ($item[$k] ?? $d));
?>

<h1 class="mb-6 text-2xl font-bold text-gray-900"><?= $isEdit ? 'Edit' : 'Add' ?> <?= e($label) ?></h1>

<div class="max-w-xl card card-body">
    <form method="post" action="<?= e($action) ?>" class="space-y-5" novalidate>
        <?= csrf_field() ?>
        <div>
            <label for="name" class="form-label"><?= e($label) ?> name *</label>
            <input type="text" id="name" name="name" value="<?= $val('name') ?>" required class="form-input">
            <?php if ($m = error_for('name')): ?><p class="form-error"><?= e($m) ?></p><?php endif; ?>
        </div>
        <div>
            <label for="description" class="form-label">Description</label>
            <textarea id="description" name="description" rows="2" class="form-textarea"><?= $val('description') ?></textarea>
        </div>
        <label class="inline-flex items-center gap-2">
            <input type="checkbox" name="is_active" value="1" <?= (old('is_active') !== '' ? old('is_active') : ($item['is_active'] ?? 1)) ? 'checked' : '' ?> class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
            <span class="text-sm text-gray-700">Active</span>
        </label>
        <div class="flex gap-2 border-t border-gray-100 pt-4">
            <button type="submit" class="btn-primary"><?= $isEdit ? 'Save changes' : 'Create' ?></button>
            <a href="<?= e(url('/masters/' . $key)) ?>" class="btn-secondary">Cancel</a>
        </div>
    </form>
</div>
