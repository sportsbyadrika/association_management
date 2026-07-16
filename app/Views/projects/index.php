<?php $this->layout('layouts.app'); /** @var list $projects */ ?>

<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Projects</h1>
        <p class="mt-1 text-sm text-gray-500">Community projects and their collections.</p>
    </div>
    <a href="<?= e(url('/projects/create')) ?>" class="btn-primary">+ New Project</a>
</div>

<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
    <?php foreach ($projects as $p): ?>
        <?php
        $target = (float) $p['target_amount'];
        $collected = (float) $p['collected'];
        $pct = $target > 0 ? min(100, (int) round($collected / $target * 100)) : 0;
        $statusBadge = match ($p['status']) {
            'completed' => 'bg-brand-100 text-brand-800',
            'active' => 'bg-blue-100 text-blue-800',
            'on_hold' => 'bg-amber-100 text-amber-800',
            'cancelled' => 'bg-gray-100 text-gray-500',
            default => 'bg-indigo-100 text-indigo-800',
        };
        ?>
        <a href="<?= e(url('/projects/' . $p['id'])) ?>" class="card card-body block transition hover:shadow-md">
            <div class="flex items-start justify-between">
                <h2 class="font-semibold text-gray-900"><?= e($p['name']) ?></h2>
                <span class="badge <?= $statusBadge ?> capitalize"><?= e(str_replace('_', ' ', $p['status'])) ?></span>
            </div>
            <p class="mt-1 text-xs text-gray-400"><?= e($p['project_type_name'] ?? 'General') ?></p>
            <?php if ($target > 0): ?>
                <div class="mt-4">
                    <div class="flex justify-between text-xs text-gray-500">
                        <span>₹ <?= money($collected) ?> collected</span>
                        <span><?= $pct ?>%</span>
                    </div>
                    <div class="mt-1 h-2 overflow-hidden rounded-full bg-gray-100">
                        <div class="h-full rounded-full bg-brand-600" style="width: <?= $pct ?>%"></div>
                    </div>
                    <p class="mt-1 text-xs text-gray-400">Target ₹ <?= money($target) ?></p>
                </div>
            <?php else: ?>
                <p class="mt-4 text-sm text-gray-500">₹ <?= money($collected) ?> collected</p>
            <?php endif; ?>
        </a>
    <?php endforeach; ?>
    <?php if ($projects === []): ?>
        <div class="col-span-full card card-body text-center text-gray-400 py-10">No projects yet. Create your first one.</div>
    <?php endif; ?>
</div>
