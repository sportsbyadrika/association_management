<?php $this->layout('layouts.guest'); /** @var string $token */ ?>

<div class="card">
    <div class="card-body">
        <h1 class="text-2xl font-bold text-gray-900">Choose a new password</h1>
        <p class="mt-1 text-sm text-gray-500">Your new password must be at least 8 characters.</p>

        <form method="post" action="<?= e(url('/password/reset')) ?>" class="mt-6 space-y-4" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="token" value="<?= e($token) ?>">
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
            <button type="submit" class="btn-primary w-full">Reset password</button>
        </form>
    </div>
</div>
<p class="mt-6 text-center text-sm text-gray-500">
    <a href="<?= e(url('/login')) ?>" class="font-medium text-brand-700 hover:text-brand-800">Back to sign in</a>
</p>
