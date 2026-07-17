<?php $this->layout('layouts.app'); ?>

<div class="mb-6 flex items-center justify-between">
    <div>
        <a href="<?= e(url('/members')) ?>" class="text-sm text-gray-500 hover:text-brand-700">&larr; Back to members</a>
        <h1 class="mt-1 text-2xl font-bold text-gray-900">Bulk Upload Members</h1>
    </div>
    <a href="<?= e(asset('/assets/members-sample.csv')) ?>" download class="btn-secondary">Download sample CSV</a>
</div>

<div class="max-w-2xl card card-body">
    <?php $steps = ['Upload', 'Review', 'Import']; $active = 0; include dirname(__DIR__) . '/partials/wizard_steps.php'; ?>

    <form method="post" action="<?= e(url('/members/bulk/parse')) ?>" enctype="multipart/form-data" class="space-y-5" novalidate>
        <?= csrf_field() ?>
        <div>
            <label for="csv" class="form-label">CSV file *</label>
            <input type="file" id="csv" name="csv" accept=".csv,text/csv" required class="block w-full text-sm text-gray-600 file:mr-4 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-brand-700">
            <?php if ($m = error_for('csv')): ?><p class="form-error"><?= e($m) ?></p><?php endif; ?>
        </div>
        <button type="submit" class="btn-primary">Upload &amp; review &rarr;</button>
    </form>

    <div class="mt-6 border-t border-gray-100 pt-5 text-sm text-gray-600">
        <p class="font-medium text-gray-800">File format</p>
        <ul class="mt-2 list-disc space-y-1 pl-5">
            <li>The first row must be a <strong>header row</strong>.</li>
            <li><strong>member_number</strong> and <strong>name</strong> are required in every row.</li>
            <li>Optional columns: <code>member_type</code> (matched by name), <code>age</code>, <code>gender</code> (male/female/other), <code>mobile</code>, <code>whatsapp</code>, <code>email</code>, <code>family_members_count</code>, <code>occupation</code>, <code>joined_on</code> (YYYY-MM-DD).</li>
            <li>You'll be able to <strong>review every row</strong> and fix issues before anything is saved.</li>
        </ul>
        <p class="mt-3 rounded-lg bg-gray-50 px-3 py-2 font-mono text-xs text-gray-600">member_number,name,member_type,age,gender,mobile,whatsapp,email,family_members_count,occupation,joined_on</p>
    </div>
</div>
