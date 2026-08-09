<?php $this->layout('layouts.app'); /** @var array $committee */ /** @var list $officials */ ?>

<div class="mb-6 flex items-center justify-between">
    <a href="<?= e(url('/committees')) ?>" class="text-sm text-gray-500 hover:text-brand-700">&larr; Back to committees</a>
    <div class="flex flex-wrap gap-2">
        <a href="<?= e(url('/committees/' . $committee['id'] . '/officials-pdf')) ?>" target="_blank" rel="noopener" class="btn-secondary btn-sm">Print officials (PDF)</a>
        <a href="<?= e(url('/committees/' . $committee['id'] . '/officials/create')) ?>" class="btn-secondary btn-sm">+ Add official</a>
        <a href="<?= e(url('/committees/' . $committee['id'] . '/edit')) ?>" class="btn-primary btn-sm">Edit</a>
    </div>
</div>

<div class="card card-body">
    <div class="flex items-start justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900"><?= e($committee['name']) ?></h1>
            <p class="mt-1 text-sm text-gray-500">
                <?php if ($committee['start_date']): ?>
                    <?= e(format_date($committee['start_date'])) ?><?= $committee['end_date'] ? ' – ' . e(format_date($committee['end_date'])) : '' ?>
                <?php endif; ?>
            </p>
        </div>
        <?php if ((int) $committee['is_active'] === 1): ?>
            <span class="badge bg-brand-100 text-brand-800">Active</span>
        <?php else: ?>
            <span class="badge bg-gray-100 text-gray-600">Inactive</span>
        <?php endif; ?>
    </div>
    <?php if (!empty($committee['description'])): ?>
        <p class="mt-4 text-gray-700"><?= nl2br(e($committee['description'])) ?></p>
    <?php endif; ?>
</div>

<div class="mt-6 card overflow-hidden">
    <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
        <h2 class="font-semibold text-gray-900">Officials <span class="text-sm font-normal text-gray-400">(<?= count($officials) ?>)</span></h2>
        <a href="<?= e(url('/committees/' . $committee['id'] . '/officials/create')) ?>" class="btn-primary btn-sm">+ Add official</a>
    </div>
    <div class="overflow-x-auto">
        <table class="table">
            <thead><tr><th>Photo</th><th>Designation</th><th>Name</th><th>Phone</th><th>Email</th><th>Login</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
            <?php foreach ($officials as $o): ?>
                <tr>
                    <td>
                        <?php if (!empty($o['photo_path'])): ?>
                            <img src="<?= e(url('/photo/official/' . $o['id'])) ?>" alt="" class="h-10 w-10 rounded-full object-cover ring-1 ring-gray-200">
                        <?php else: ?>
                            <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-brand-100 text-sm font-semibold text-brand-700"><?= e(strtoupper(substr($o['name'], 0, 1))) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="font-medium text-gray-900"><?= e($o['designation'] ?? '—') ?></td>
                    <td><?= e($o['name']) ?></td>
                    <td><?= e($o['phone'] ?? '—') ?></td>
                    <td><?= e($o['email'] ?? '—') ?></td>
                    <td>
                        <?php if (!empty($o['login_email'])): ?>
                            <span class="badge bg-sky-100 text-sky-800"><?= e(str_replace('association_', '', (string) $o['login_role'])) ?></span>
                        <?php else: ?>
                            <span class="text-gray-300">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="whitespace-nowrap text-right">
                        <a href="<?= e(url('/officials/' . $o['id'] . '/edit')) ?>" class="text-brand-700 hover:underline">Edit</a>
                        <span class="text-gray-300">·</span>
                        <form method="post" action="<?= e(url('/officials/' . $o['id'] . '/delete')) ?>" class="inline" data-confirm="Remove this official?">
                            <?= csrf_field() ?>
                            <button type="submit" class="text-red-600 hover:underline">Remove</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($officials === []): ?>
                <tr><td colspan="7" class="text-center text-gray-400 py-8">No officials added yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
