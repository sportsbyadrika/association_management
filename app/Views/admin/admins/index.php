<?php $this->layout('layouts.app'); /** @var list $admins */ /** @var list $associations */ ?>

<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Association Admins</h1>
        <p class="mt-1 text-sm text-gray-500">Admin accounts across all associations.</p>
    </div>
</div>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="table">
            <thead>
                <tr><th>Name</th><th>Email</th><th>Association</th><th>Status</th><th class="text-right">Actions</th></tr>
            </thead>
            <tbody>
            <?php foreach ($admins as $u): ?>
                <tr>
                    <td class="font-medium text-gray-900"><?= e($u['name']) ?></td>
                    <td><?= e($u['email']) ?></td>
                    <td><?= e($u['association_name'] ?? '—') ?></td>
                    <td>
                        <?php if ((int) $u['is_active'] === 1): ?>
                            <span class="badge bg-brand-100 text-brand-800">Active</span>
                        <?php else: ?>
                            <span class="badge bg-gray-100 text-gray-600">Disabled</span>
                        <?php endif; ?>
                        <?php if ((int) $u['must_change_password'] === 1): ?>
                            <span class="badge bg-amber-100 text-amber-800">Pwd change pending</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-right">
                        <a href="<?= e(url('/admin/admins/' . $u['id'] . '/edit')) ?>" class="text-brand-700 hover:underline">Edit</a>
                        <span class="text-gray-300">·</span>
                        <button type="button" data-dropdown-toggle="#reset-<?= (int) $u['id'] ?>" class="text-brand-700 hover:underline">Reset password</button>
                        <div id="reset-<?= (int) $u['id'] ?>" data-dropdown class="hidden mt-2 rounded-lg border border-gray-200 bg-gray-50 p-3 text-left">
                            <form method="post" action="<?= e(url('/admin/admins/' . $u['id'] . '/reset-password')) ?>" class="flex items-center gap-2">
                                <?= csrf_field() ?>
                                <input type="password" name="password" placeholder="New password" required minlength="8" class="form-input text-sm">
                                <button type="submit" class="btn-primary btn-sm">Set</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($admins === []): ?>
                <tr><td colspan="5" class="text-center text-gray-400 py-8">No admin accounts yet. Add one from an association.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($associations !== []): ?>
<div class="mt-6 card card-body">
    <h2 class="font-semibold text-gray-900">Add an admin to an association</h2>
    <div class="mt-3 flex flex-wrap gap-2">
        <?php foreach ($associations as $a): ?>
            <a href="<?= e(url('/admin/associations/' . $a['id'] . '/admins/create')) ?>" class="btn-secondary btn-sm"><?= e($a['name']) ?></a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>
