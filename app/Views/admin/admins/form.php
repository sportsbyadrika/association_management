<?php $this->layout('layouts.app'); /** @var array|null $admin */ /** @var array $association */
$isEdit = $admin !== null;
$action = $isEdit ? url('/admin/admins/' . $admin['id']) : url('/admin/associations/' . $association['id'] . '/admins');
$val = static fn (string $k, $d = '') => e(old($k) !== '' ? old($k) : ($admin[$k] ?? $d));
$currentRole = old('role') !== '' ? old('role') : ($admin['role'] ?? 'association_admin');
?>

<h1 class="mb-1 text-2xl font-bold text-gray-900"><?= $isEdit ? 'Edit' : 'Add' ?> Account</h1>
<p class="mb-6 text-sm text-gray-500"><?= e($association['name'] ?? '') ?></p>

<div class="max-w-2xl card card-body">
    <form method="post" action="<?= e($action) ?>" class="space-y-5" novalidate>
        <?= csrf_field() ?>
        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="name" class="form-label">Full name *</label>
                <input type="text" id="name" name="name" value="<?= $val('name') ?>" required class="form-input">
                <?php if ($m = error_for('name')): ?><p class="form-error"><?= e($m) ?></p><?php endif; ?>
            </div>
            <div>
                <label for="email" class="form-label">Email (login) *</label>
                <input type="email" id="email" name="email" value="<?= $val('email') ?>" required class="form-input">
                <?php if ($m = error_for('email')): ?><p class="form-error"><?= e($m) ?></p><?php endif; ?>
            </div>
            <div>
                <label for="role" class="form-label">Role</label>
                <select id="role" name="role" class="form-select">
                    <option value="association_admin" <?= $currentRole === 'association_admin' ? 'selected' : '' ?>>Association Admin</option>
                    <option value="association_staff" <?= $currentRole === 'association_staff' ? 'selected' : '' ?>>Staff</option>
                </select>
            </div>
            <?php if (!$isEdit): ?>
                <div>
                    <label for="password" class="form-label">Temporary password *</label>
                    <input type="password" id="password" name="password" required minlength="8" class="form-input">
                    <?php if ($m = error_for('password')): ?><p class="form-error"><?= e($m) ?></p><?php endif; ?>
                    <p class="mt-1 text-xs text-gray-400">User must change it on first login.</p>
                </div>
            <?php else: ?>
                <div class="flex items-end">
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" name="is_active" value="1" <?= (old('is_active') !== '' ? old('is_active') : ($admin['is_active'] ?? 1)) ? 'checked' : '' ?> class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                        <span class="text-sm text-gray-700">Active (can log in)</span>
                    </label>
                </div>
            <?php endif; ?>
        </div>
        <div class="flex gap-2 border-t border-gray-100 pt-4">
            <button type="submit" class="btn-primary"><?= $isEdit ? 'Save changes' : 'Create account' ?></button>
            <a href="<?= e(url('/admin/admins')) ?>" class="btn-secondary">Cancel</a>
        </div>
    </form>
</div>
