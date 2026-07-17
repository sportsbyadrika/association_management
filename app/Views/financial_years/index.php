<?php $this->layout('layouts.app'); /** @var list $items */ /** @var array|null $current */ ?>

<h1 class="mb-6 text-2xl font-bold text-gray-900">Masters</h1>
<?php include dirname(__DIR__) . '/partials/masters_tabs.php'; ?>

<div class="mb-6 flex items-center justify-between">
    <div>
        <h2 class="text-lg font-semibold text-gray-900">Financial Years</h2>
        <p class="mt-1 text-sm text-gray-500">Define the financial years used across reports and filters.</p>
    </div>
    <a href="<?= e(url('/masters/financial-years/create')) ?>" class="btn-primary">+ Add Financial Year</a>
</div>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="table">
            <thead><tr><th>Label</th><th>From</th><th>To</th><th>Status</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td class="font-medium text-gray-900">
                        <?= e($item['label']) ?>
                        <?php if ($current && (int) $current['id'] === (int) $item['id']): ?>
                            <span class="badge bg-brand-100 text-brand-800">Current</span>
                        <?php endif; ?>
                    </td>
                    <td><?= e(format_date($item['start_date'])) ?></td>
                    <td><?= e(format_date($item['end_date'])) ?></td>
                    <td>
                        <?php if ((int) $item['is_active'] === 1): ?>
                            <span class="badge bg-brand-100 text-brand-800">Active</span>
                        <?php else: ?>
                            <span class="badge bg-gray-100 text-gray-600">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-right">
                        <a href="<?= e(url('/masters/financial-years/' . $item['id'] . '/edit')) ?>" class="text-brand-700 hover:underline">Edit</a>
                        <span class="text-gray-300">·</span>
                        <form method="post" action="<?= e(url('/masters/financial-years/' . $item['id'] . '/toggle')) ?>" class="inline">
                            <?= csrf_field() ?>
                            <button type="submit" class="text-gray-500 hover:underline"><?= (int) $item['is_active'] === 1 ? 'Deactivate' : 'Activate' ?></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($items === []): ?>
                <tr><td colspan="5" class="text-center text-gray-400 py-8">No financial years yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
