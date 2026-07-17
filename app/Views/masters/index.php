<?php $this->layout('layouts.app'); /** @var string $key */ /** @var array $tabs */ /** @var list $items */ ?>

<div class="mb-6 flex items-center justify-between">
    <h1 class="text-2xl font-bold text-gray-900">Masters</h1>
    <a href="<?= e(url('/masters/' . $key . '/create')) ?>" class="btn-primary">+ Add <?= e($label) ?></a>
</div>

<?php include dirname(__DIR__) . '/partials/masters_tabs.php'; ?>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="table">
            <thead><tr><th><?= e($label) ?></th><th>Description</th><th>Status</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td class="font-medium text-gray-900"><?= e($item['name']) ?></td>
                    <td class="text-gray-500"><?= e($item['description'] ?? '—') ?></td>
                    <td>
                        <?php if ((int) $item['is_active'] === 1): ?>
                            <span class="badge bg-brand-100 text-brand-800">Active</span>
                        <?php else: ?>
                            <span class="badge bg-gray-100 text-gray-600">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-right">
                        <a href="<?= e(url('/masters/' . $key . '/' . $item['id'] . '/edit')) ?>" class="text-brand-700 hover:underline">Edit</a>
                        <span class="text-gray-300">·</span>
                        <form method="post" action="<?= e(url('/masters/' . $key . '/' . $item['id'] . '/toggle')) ?>" class="inline">
                            <?= csrf_field() ?>
                            <button type="submit" class="text-gray-500 hover:underline"><?= (int) $item['is_active'] === 1 ? 'Deactivate' : 'Activate' ?></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($items === []): ?>
                <tr><td colspan="4" class="text-center text-gray-400 py-8">No <?= e(strtolower($label)) ?>s yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
