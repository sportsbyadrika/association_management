<?php $this->layout('layouts.app'); /** @var list $associations */ ?>

<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Associations</h1>
        <p class="mt-1 text-sm text-gray-500">Manage associations and their subscriptions.</p>
    </div>
    <a href="<?= e(url('/admin/associations/create')) ?>" class="btn-primary">+ Add Association</a>
</div>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th><th>Contact</th><th>Subscription</th><th>Status</th><th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($associations as $a): ?>
                <tr>
                    <td>
                        <div class="font-medium text-gray-900"><?= e($a['name']) ?></div>
                        <div class="text-xs text-gray-400"><?= e($a['address'] ?? '') ?></div>
                    </td>
                    <td>
                        <div><?= e($a['contact_email'] ?? '—') ?></div>
                        <div class="text-xs text-gray-400"><?= e($a['contact_phone'] ?? '') ?></div>
                    </td>
                    <td>
                        <div class="text-xs"><?= e(format_date($a['subscription_start'])) ?> → <?= e(format_date($a['subscription_end'])) ?></div>
                    </td>
                    <td>
                        <?php if ($a['sub_active']): ?>
                            <span class="badge bg-brand-100 text-brand-800">Active</span>
                        <?php else: ?>
                            <span class="badge bg-red-100 text-red-800">Inactive / Expired</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-right">
                        <a href="<?= e(url('/admin/associations/' . $a['id'] . '/edit')) ?>" class="text-brand-700 hover:underline">Edit</a>
                        <span class="text-gray-300">·</span>
                        <a href="<?= e(url('/admin/associations/' . $a['id'] . '/subscription')) ?>" class="text-brand-700 hover:underline">Subscription</a>
                        <span class="text-gray-300">·</span>
                        <a href="<?= e(url('/admin/associations/' . $a['id'] . '/admins/create')) ?>" class="text-brand-700 hover:underline">Add admin</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($associations === []): ?>
                <tr><td colspan="5" class="text-center text-gray-400 py-8">No associations yet. Add your first one.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
