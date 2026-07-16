<?php $this->layout('layouts.guest'); ?>

<div class="card">
    <div class="card-body">
        <h1 class="text-2xl font-bold text-gray-900">Forgot your password?</h1>
        <p class="mt-1 text-sm text-gray-500">Enter your email and we'll send you a link to reset it.</p>

        <form method="post" action="<?= e(url('/password/forgot')) ?>" class="mt-6 space-y-4" novalidate>
            <?= csrf_field() ?>
            <div>
                <label for="email" class="form-label">Email address</label>
                <input type="email" id="email" name="email" value="<?= old('email') ?>"
                       autocomplete="username" required autofocus
                       class="form-input <?= has_error('email') ? 'border-red-400' : '' ?>">
                <?php if ($msg = error_for('email')): ?><p class="form-error"><?= e($msg) ?></p><?php endif; ?>
            </div>
            <button type="submit" class="btn-primary w-full">Send reset link</button>
        </form>
    </div>
</div>
<p class="mt-6 text-center text-sm text-gray-500">
    Remembered it? <a href="<?= e(url('/login')) ?>" class="font-medium text-brand-700 hover:text-brand-800">Back to sign in</a>
</p>
