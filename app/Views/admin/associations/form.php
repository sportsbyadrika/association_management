<?php $this->layout('layouts.app'); /** @var array|null $association */
$isEdit = $association !== null;
$action = $isEdit ? url('/admin/associations/' . $association['id']) : url('/admin/associations');
$val = static fn (string $k, $d = '') => e(old($k) !== '' ? old($k) : ($association[$k] ?? $d));
?>

<h1 class="mb-6 text-2xl font-bold text-gray-900"><?= $isEdit ? 'Edit' : 'Add' ?> Association</h1>

<div class="max-w-3xl card card-body">
    <form method="post" action="<?= e($action) ?>" enctype="multipart/form-data" class="space-y-5" novalidate>
        <?= csrf_field() ?>
        <div class="grid gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="name" class="form-label">Association name *</label>
                <input type="text" id="name" name="name" value="<?= $val('name') ?>" required class="form-input">
                <?php if ($m = error_for('name')): ?><p class="form-error"><?= e($m) ?></p><?php endif; ?>
            </div>
            <div>
                <label for="contact_email" class="form-label">Contact email</label>
                <input type="email" id="contact_email" name="contact_email" value="<?= $val('contact_email') ?>" class="form-input">
                <?php if ($m = error_for('contact_email')): ?><p class="form-error"><?= e($m) ?></p><?php endif; ?>
            </div>
            <div>
                <label for="contact_phone" class="form-label">Contact phone</label>
                <input type="text" id="contact_phone" name="contact_phone" value="<?= $val('contact_phone') ?>" class="form-input">
                <?php if ($m = error_for('contact_phone')): ?><p class="form-error"><?= e($m) ?></p><?php endif; ?>
            </div>
            <div class="sm:col-span-2">
                <label for="address" class="form-label">Address</label>
                <textarea id="address" name="address" rows="2" class="form-textarea"><?= $val('address') ?></textarea>
            </div>
            <div>
                <label for="subscription_start" class="form-label">Subscription start</label>
                <input type="date" id="subscription_start" name="subscription_start" value="<?= $val('subscription_start') ?>" class="form-input">
            </div>
            <div>
                <label for="subscription_end" class="form-label">Subscription end</label>
                <input type="date" id="subscription_end" name="subscription_end" value="<?= $val('subscription_end') ?>" class="form-input">
            </div>
            <div class="sm:col-span-2">
                <label for="logo" class="form-label">Logo (JPEG/PNG/WebP, max 3&nbsp;MB)</label>
                <?php if ($isEdit && !empty($association['logo_path'])): ?>
                    <img src="<?= e(url('/photo/association/' . $association['id'])) ?>" alt="" class="mb-2 h-16 w-16 rounded-lg object-cover ring-1 ring-gray-200">
                <?php endif; ?>
                <input type="file" id="logo" name="logo" accept="image/jpeg,image/png,image/webp" data-crop="square" class="block w-full text-sm text-gray-600 file:mr-4 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-brand-700">
                <?php if ($m = error_for('logo')): ?><p class="form-error"><?= e($m) ?></p><?php endif; ?>
                <p class="mt-1 text-xs text-gray-400">You can crop to a square after choosing.</p>
            </div>
            <div class="sm:col-span-2">
                <label class="inline-flex items-center gap-2">
                    <input type="checkbox" name="is_active" value="1" <?= (old('is_active') !== '' ? old('is_active') : ($association['is_active'] ?? 1)) ? 'checked' : '' ?> class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                    <span class="text-sm text-gray-700">Active</span>
                </label>
            </div>
        </div>
        <div class="flex gap-2 border-t border-gray-100 pt-4">
            <button type="submit" class="btn-primary"><?= $isEdit ? 'Save changes' : 'Create association' ?></button>
            <a href="<?= e(url('/admin/associations')) ?>" class="btn-secondary">Cancel</a>
        </div>
    </form>
</div>
