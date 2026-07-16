<?php $this->layout('layouts.app'); ?>

<h1 class="mb-6 text-2xl font-bold text-gray-900">Change Password</h1>

<div class="max-w-lg card card-body">
    <form method="post" action="<?= e(url('/profile/password')) ?>" class="space-y-4" novalidate>
        <?= csrf_field() ?>
        <div>
            <label for="current_password" class="form-label">Current password</label>
            <input type="password" id="current_password" name="current_password" autocomplete="current-password" required class="form-input">
            <?php if ($m = error_for('current_password')): ?><p class="form-error"><?= e($m) ?></p><?php endif; ?>
        </div>
        <div>
            <label for="password" class="form-label">New password</label>
            <input type="password" id="password" name="password" autocomplete="new-password" required class="form-input">
            <?php if ($m = error_for('password')): ?><p class="form-error"><?= e($m) ?></p><?php endif; ?>
            <p class="mt-1 text-xs text-gray-400">At least 8 characters.</p>
        </div>
        <div>
            <label for="password_confirmation" class="form-label">Confirm new password</label>
            <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required class="form-input">
        </div>
        <div class="flex gap-2">
            <button type="submit" class="btn-primary">Update password</button>
            <a href="<?= e(url('/profile')) ?>" class="btn-secondary">Cancel</a>
        </div>
    </form>
</div>
