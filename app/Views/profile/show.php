<?php $this->layout('layouts.app'); /** @var array $user */ ?>

<h1 class="mb-6 text-2xl font-bold text-gray-900">My Profile</h1>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="card card-body lg:col-span-2">
        <form method="post" action="<?= e(url('/profile')) ?>" class="space-y-4">
            <?= csrf_field() ?>
            <div>
                <label for="name" class="form-label">Full name</label>
                <input type="text" id="name" name="name" value="<?= e($user['name']) ?>" required class="form-input">
                <?php if ($m = error_for('name')): ?><p class="form-error"><?= e($m) ?></p><?php endif; ?>
            </div>
            <div>
                <label class="form-label">Email</label>
                <input type="email" value="<?= e($user['email']) ?>" disabled class="form-input bg-gray-50 text-gray-500">
                <p class="mt-1 text-xs text-gray-400">Your email is your login and cannot be changed here.</p>
            </div>
            <div>
                <label class="form-label">Role</label>
                <input type="text" value="<?= e(role_label($user['role'])) ?>" disabled class="form-input bg-gray-50 text-gray-500">
            </div>
            <button type="submit" class="btn-primary">Save changes</button>
        </form>
    </div>

    <div class="card card-body">
        <h2 class="font-semibold text-gray-900">Account</h2>
        <dl class="mt-4 space-y-3 text-sm">
            <div>
                <dt class="text-gray-500">Last login</dt>
                <dd class="font-medium text-gray-900"><?= e(format_date($user['last_login_at'] ?? null, 'd M Y, H:i')) ?></dd>
            </div>
            <div>
                <dt class="text-gray-500">Member since</dt>
                <dd class="font-medium text-gray-900"><?= e(format_date($user['created_at'] ?? null)) ?></dd>
            </div>
        </dl>
        <a href="<?= e(url('/profile/password')) ?>" class="btn-secondary mt-6 w-full">Change password</a>
    </div>
</div>
