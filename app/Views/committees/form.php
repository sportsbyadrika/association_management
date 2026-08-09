<?php $this->layout('layouts.app'); /** @var array|null $committee */
$c = $committee ?? null;
$isEdit = $c !== null;
$action = $isEdit ? url('/committees/' . $c['id']) : url('/committees');
$val = static fn (string $k, $d = '') => e(old($k) !== '' ? old($k) : ($c[$k] ?? $d));
$isActive = $isEdit ? ((int) ($c['is_active'] ?? 1) === 1) : true;
if (old('name') !== '' || \App\Core\Session::errors() !== []) {
    $isActive = old('is_active') !== '';
}
?>

<h1 class="mb-6 text-2xl font-bold text-gray-900"><?= $isEdit ? 'Edit' : 'Add' ?> Committee</h1>

<div class="max-w-2xl card card-body">
    <form method="post" action="<?= e($action) ?>" class="space-y-5" novalidate>
        <?= csrf_field() ?>
        <div class="grid gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="name" class="form-label">Committee name *</label>
                <input type="text" id="name" name="name" value="<?= $val('name') ?>" required autofocus class="form-input" placeholder="e.g. Managing Committee 2026–27">
                <?php if ($m = error_for('name')): ?><p class="form-error"><?= e($m) ?></p><?php endif; ?>
            </div>
            <div>
                <label for="start_date" class="form-label">Period start</label>
                <input type="date" id="start_date" name="start_date" value="<?= $val('start_date') ?>" class="form-input">
                <?php if ($m = error_for('start_date')): ?><p class="form-error"><?= e($m) ?></p><?php endif; ?>
            </div>
            <div>
                <label for="end_date" class="form-label">Period end</label>
                <input type="date" id="end_date" name="end_date" value="<?= $val('end_date') ?>" class="form-input">
                <?php if ($m = error_for('end_date')): ?><p class="form-error"><?= e($m) ?></p><?php endif; ?>
            </div>
            <div class="sm:col-span-2">
                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="is_active" value="1" <?= $isActive ? 'checked' : '' ?> class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                    Active committee
                </label>
            </div>
            <div class="sm:col-span-2">
                <label for="description" class="form-label">Description</label>
                <textarea id="description" name="description" rows="2" class="form-textarea"><?= $val('description') ?></textarea>
            </div>
        </div>
        <div class="flex gap-2 border-t border-gray-100 pt-4">
            <button type="submit" class="btn-primary"><?= $isEdit ? 'Save changes' : 'Save committee' ?></button>
            <a href="<?= e(url($isEdit ? '/committees/' . $c['id'] : '/committees')) ?>" class="btn-secondary">Cancel</a>
        </div>
    </form>
</div>
