<?php $this->layout('layouts.guest'); ?>

<div class="card">
    <div class="card-body">
        <h1 class="text-2xl font-bold text-gray-900">Update your password</h1>
        <p class="mt-1 text-sm text-gray-500">For your security, please set a new password before continuing.</p>

        <form method="post" action="<?= e(url('/password/force-change')) ?>" class="mt-6 space-y-4" novalidate>
            <?= csrf_field() ?>
            <div>
                <label for="current_password" class="form-label">Current password</label>
                <input type="password" id="current_password" name="current_password" autocomplete="current-password" required
                       class="form-input <?= has_error('current_password') ? 'border-red-400' : '' ?>">
                <?php if ($msg = error_for('current_password')): ?><p class="form-error"><?= e($msg) ?></p><?php endif; ?>
            </div>
            <div>
                <label for="password" class="form-label">New password</label>
                <input type="password" id="password" name="password" autocomplete="new-password" required
                       class="form-input <?= has_error('password') ? 'border-red-400' : '' ?>">
                <?php if ($msg = error_for('password')): ?><p class="form-error"><?= e($msg) ?></p><?php endif; ?>
            </div>
            <div>
                <label for="password_confirmation" class="form-label">Confirm new password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required
                       class="form-input">
            </div>
            <button type="submit" class="btn-primary w-full">Save and continue</button>
        </form>
    </div>
</div>
<form method="post" action="<?= e(url('/logout')) ?>" class="mt-6 text-center">
    <?= csrf_field() ?>
    <button type="submit" class="text-sm font-medium text-gray-500 hover:text-brand-700">Sign out</button>
</form>
