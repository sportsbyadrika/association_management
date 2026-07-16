<?php $this->layout('layouts.guest'); ?>

<div class="card">
    <div class="card-body">
        <h1 class="text-2xl font-bold text-gray-900">Sign in</h1>
        <p class="mt-1 text-sm text-gray-500">Access your Habitract account.</p>

        <form method="post" action="<?= e(url('/login')) ?>" class="mt-6 space-y-4" novalidate>
            <?= csrf_field() ?>
            <div>
                <label for="email" class="form-label">Email address</label>
                <input type="email" id="email" name="email" value="<?= old('email') ?>"
                       autocomplete="username" required autofocus
                       class="form-input <?= has_error('email') ? 'border-red-400' : '' ?>">
                <?php if ($msg = error_for('email')): ?><p class="form-error"><?= e($msg) ?></p><?php endif; ?>
            </div>
            <div>
                <div class="flex items-center justify-between">
                    <label for="password" class="form-label">Password</label>
                    <a href="<?= e(url('/password/forgot')) ?>" class="text-sm font-medium text-brand-700 hover:text-brand-800">Forgot password?</a>
                </div>
                <input type="password" id="password" name="password" autocomplete="current-password" required
                       class="form-input <?= has_error('password') ? 'border-red-400' : '' ?>">
                <?php if ($msg = error_for('password')): ?><p class="form-error"><?= e($msg) ?></p><?php endif; ?>
            </div>
            <button type="submit" class="btn-primary w-full">Sign in</button>
        </form>
    </div>
</div>
<p class="mt-6 text-center text-sm text-gray-500">
    New here? <a href="<?= e(url('/')) ?>" class="font-medium text-brand-700 hover:text-brand-800">Learn about Habitract</a>
</p>
