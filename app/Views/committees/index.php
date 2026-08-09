<?php $this->layout('layouts.app'); /** @var list $committees */ ?>

<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Committees</h1>
        <p class="mt-1 text-sm text-gray-500">Committee periods and their officials.</p>
    </div>
    <a href="<?= e(url('/committees/create')) ?>" class="btn-primary">+ Add Committee</a>
</div>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="table">
            <thead><tr><th>Committee</th><th>Period</th><th class="text-right">Officials</th><th>Status</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
            <?php foreach ($committees as $c): ?>
                <tr>
                    <td class="font-medium text-gray-900"><a href="<?= e(url('/committees/' . $c['id'])) ?>" class="text-brand-700 hover:underline"><?= e($c['name']) ?></a></td>
                    <td class="text-sm text-gray-600">
                        <?php if ($c['start_date']): ?>
                            <?= e(format_date($c['start_date'])) ?><?= $c['end_date'] ? ' – ' . e(format_date($c['end_date'])) : '' ?>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td class="text-right"><?= (int) $c['official_count'] ?></td>
                    <td>
                        <?php if ((int) $c['is_active'] === 1): ?>
                            <span class="badge bg-brand-100 text-brand-800">Active</span>
                        <?php else: ?>
                            <span class="badge bg-gray-100 text-gray-600">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td class="whitespace-nowrap text-right">
                        <a href="<?= e(url('/committees/' . $c['id'] . '/officials-pdf')) ?>" target="_blank" rel="noopener" class="text-brand-700 hover:underline">PDF</a>
                        <span class="text-gray-300">·</span>
                        <a href="<?= e(url('/committees/' . $c['id'] . '/edit')) ?>" class="text-brand-700 hover:underline">Edit</a>
                        <span class="text-gray-300">·</span>
                        <form method="post" action="<?= e(url('/committees/' . $c['id'] . '/delete')) ?>" class="inline" data-confirm="Delete this committee and its officials?">
                            <?= csrf_field() ?>
                            <button type="submit" class="text-red-600 hover:underline">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($committees === []): ?>
                <tr><td colspan="5" class="text-center text-gray-400 py-8">No committees yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
