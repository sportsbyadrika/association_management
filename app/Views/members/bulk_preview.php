<?php $this->layout('layouts.app');
/** @var array $parsed */ /** @var string $fileName */
$rows = $parsed['rows'];
$valid = (int) $parsed['validCount'];
$invalid = (int) $parsed['invalidCount'];
?>

<div class="mb-6">
    <a href="<?= e(url('/members/bulk')) ?>" class="text-sm text-gray-500 hover:text-brand-700">&larr; Choose a different file</a>
    <h1 class="mt-1 text-2xl font-bold text-gray-900">Bulk Upload — Review</h1>
    <p class="mt-1 text-sm text-gray-500">File: <span class="font-medium text-gray-700"><?= e($fileName) ?></span></p>
</div>

<div class="card card-body">
    <?php $steps = ['Upload', 'Review', 'Import']; $active = 1; include dirname(__DIR__) . '/partials/wizard_steps.php'; ?>

    <div class="mb-4 flex flex-wrap gap-3">
        <span class="badge bg-brand-100 text-brand-800"><?= $valid ?> ready to import</span>
        <?php if ($invalid > 0): ?>
            <span class="badge bg-red-100 text-red-800"><?= $invalid ?> with issues (will be skipped)</span>
        <?php endif; ?>
        <span class="badge bg-gray-100 text-gray-600"><?= count($rows) ?> total rows</span>
    </div>

    <div class="overflow-x-auto rounded-lg ring-1 ring-gray-200">
        <table class="table">
            <thead>
                <tr><th>Row</th><th>Status</th><th>Member No.</th><th>Name</th><th>Type</th><th>Mobile</th><th>Email</th><th>Issue</th></tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr class="<?= $r['valid'] ? '' : 'bg-red-50' ?>">
                    <td class="text-gray-400"><?= (int) $r['line'] ?></td>
                    <td>
                        <?php if ($r['valid']): ?>
                            <span class="badge bg-brand-100 text-brand-800">OK</span>
                        <?php else: ?>
                            <span class="badge bg-red-100 text-red-800">Skip</span>
                        <?php endif; ?>
                    </td>
                    <td class="font-medium text-gray-800"><?= e($r['display']['member_number'] ?: '—') ?></td>
                    <td><?= e($r['display']['name'] ?: '—') ?></td>
                    <td><?= e($r['display']['member_type'] ?: '—') ?></td>
                    <td><?= e($r['display']['mobile'] ?: '—') ?></td>
                    <td><?= e($r['display']['email'] ?: '—') ?></td>
                    <td class="text-red-600"><?= e($r['error'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($rows === []): ?>
                <tr><td colspan="8" class="text-center text-gray-400 py-8">No data rows found in the file.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="mt-6 flex items-center gap-3 border-t border-gray-100 pt-5">
        <?php if ($valid > 0): ?>
            <form method="post" action="<?= e(url('/members/bulk/import')) ?>">
                <?= csrf_field() ?>
                <button type="submit" class="btn-primary">Import <?= $valid ?> member<?= $valid === 1 ? '' : 's' ?> &rarr;</button>
            </form>
        <?php else: ?>
            <p class="text-sm text-red-600">No valid rows to import. Fix the issues above and upload again.</p>
        <?php endif; ?>
        <a href="<?= e(url('/members/bulk')) ?>" class="btn-secondary">Upload a different file</a>
    </div>
</div>
