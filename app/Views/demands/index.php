<?php $this->layout('layouts.app'); /** @var list $demands */ /** @var array $paginator */ ?>

<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Demands</h1>
        <p class="mt-1 text-sm text-gray-500">Charges raised against members.</p>
    </div>
    <a href="<?= e(url('/demands/create')) ?>" class="btn-primary">+ Raise Demand</a>
</div>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="table">
            <thead><tr><th>Member</th><th>Purpose</th><th>Project</th><th>Due</th><th class="text-right">Amount</th><th>Status</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
            <?php foreach ($demands as $d): ?>
                <tr>
                    <td class="font-medium text-gray-900"><?= e($d['member_name']) ?></td>
                    <td class="capitalize"><?= e($d['purpose']) ?></td>
                    <td><?= e($d['project_name'] ?? '—') ?></td>
                    <td><?= e(format_date($d['due_date'])) ?></td>
                    <td class="text-right font-medium">₹ <?= money($d['amount']) ?></td>
                    <td>
                        <?php
                        $badge = match ($d['status']) {
                            'paid' => 'bg-brand-100 text-brand-800',
                            'partial' => 'bg-blue-100 text-blue-800',
                            'cancelled' => 'bg-gray-100 text-gray-500',
                            default => 'bg-amber-100 text-amber-800',
                        }; ?>
                        <span class="badge <?= $badge ?> capitalize"><?= e($d['status']) ?></span>
                    </td>
                    <td class="text-right">
                        <?php if ($d['status'] !== 'cancelled'): ?>
                            <form method="post" action="<?= e(url('/demands/' . $d['id'] . '/delete')) ?>" class="inline" data-confirm="Cancel this demand?">
                                <?= csrf_field() ?>
                                <button type="submit" class="text-red-600 hover:underline">Cancel</button>
                            </form>
                        <?php else: ?>
                            <span class="text-gray-300">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($demands === []): ?>
                <tr><td colspan="7" class="text-center text-gray-400 py-8">No demands yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="p-4"><?php $baseUrl = url('/demands'); include dirname(__DIR__) . '/partials/pagination.php'; ?></div>
</div>
