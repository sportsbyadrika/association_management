<?php $this->layout('layouts.app');
/** @var array $project */ /** @var list $milestones */ /** @var float $collected */ /** @var float $spent */
/** @var list $received */ /** @var list $pending */ /** @var array $demandTotals */
$target = (float) $project['target_amount'];
$pct = $target > 0 ? min(100, (int) round($collected / $target * 100)) : 0;

$renderMemberList = static function (array $list): void {
    ?>
    <div class="overflow-x-auto">
        <table class="table">
            <thead><tr>
                <th>Member No.</th><th>Name</th>
                <th class="text-right">Demand</th><th class="text-right">Collected</th><th class="text-right">Balance</th>
                <th>Received On</th>
            </tr></thead>
            <tbody>
            <?php foreach ($list as $row): ?>
                <tr>
                    <td class="font-medium text-gray-700"><?= e($row['member_number'] ?: '—') ?></td>
                    <td><?= e($row['name']) ?></td>
                    <td class="text-right">₹ <?= money($row['amount']) ?></td>
                    <td class="text-right text-brand-700">₹ <?= money($row['collected']) ?></td>
                    <td class="text-right <?= $row['balance'] > 0 ? 'text-amber-600' : 'text-gray-400' ?>">₹ <?= money($row['balance']) ?></td>
                    <td><?= $row['received_on'] ? e(format_date($row['received_on'])) : '—' ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($list === []): ?>
                <tr><td colspan="6" class="text-center text-gray-400 py-6">No members.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
};
?>

<div class="mb-6 flex items-center justify-between">
    <a href="<?= e(url('/projects')) ?>" class="text-sm text-gray-500 hover:text-brand-700">&larr; Back to projects</a>
    <div class="flex flex-wrap gap-2">
        <a href="<?= e(url('/demands/create?project_id=' . $project['id'])) ?>" class="btn-secondary btn-sm">Add demand</a>
        <a href="<?= e(url('/receipts/create?project_id=' . $project['id'])) ?>" class="btn-secondary btn-sm">Add collection</a>
        <a href="<?= e(url('/expenditures/create?project_id=' . $project['id'])) ?>" class="btn-secondary btn-sm">Add expenditure</a>
        <a href="<?= e(url('/projects/' . $project['id'] . '/ledger')) ?>" target="_blank" rel="noopener" class="btn-secondary btn-sm">Print Ledger</a>
        <a href="<?= e(url('/projects/' . $project['id'] . '/edit')) ?>" class="btn-primary btn-sm">Edit</a>
    </div>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="lg:col-span-2 space-y-6">
        <div class="card card-body">
            <div class="flex items-start justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900"><?= e($project['name']) ?></h1>
                    <p class="text-sm text-gray-500"><?= e($project['project_type_name'] ?? 'General') ?> · <span class="capitalize"><?= e(str_replace('_', ' ', $project['status'])) ?></span></p>
                </div>
            </div>
            <?php if (!empty($project['description'])): ?>
                <p class="mt-4 text-gray-700"><?= nl2br(e($project['description'])) ?></p>
            <?php endif; ?>
            <div class="mt-4 grid grid-cols-2 gap-4 text-sm sm:grid-cols-4">
                <div><p class="text-gray-500">Target</p><p class="font-semibold">₹ <?= money($target) ?></p></div>
                <div><p class="text-gray-500">Collected</p><p class="font-semibold text-brand-700">₹ <?= money($collected) ?></p></div>
                <div><p class="text-gray-500">Spent</p><p class="font-semibold text-red-600">₹ <?= money($spent) ?></p></div>
                <div><p class="text-gray-500">Balance</p><p class="font-semibold">₹ <?= money($collected - $spent) ?></p></div>
            </div>
            <?php if ($target > 0): ?>
                <div class="mt-4 h-2 overflow-hidden rounded-full bg-gray-100">
                    <div class="h-full rounded-full bg-brand-600" style="width: <?= $pct ?>%"></div>
                </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <div class="border-b border-gray-100 px-6 py-4">
                <h2 class="font-semibold text-gray-900">Milestones</h2>
            </div>
            <div class="divide-y divide-gray-100">
                <?php foreach ($milestones as $ms): ?>
                    <div class="flex gap-4 p-6">
                        <?php if (!empty($ms['photo_path'])): ?>
                            <img src="<?= e(url('/photo/milestone/' . $ms['id'])) ?>" alt="" class="h-20 w-20 flex-shrink-0 rounded-lg object-cover ring-1 ring-gray-200">
                        <?php endif; ?>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="font-medium text-gray-900"><?= e($ms['title']) ?></h3>
                                <?php if (!empty($ms['achieved_on'])): ?><span class="text-xs text-gray-400"><?= e(format_date($ms['achieved_on'])) ?></span><?php endif; ?>
                            </div>
                            <?php if (!empty($ms['description'])): ?><p class="mt-1 text-sm text-gray-600"><?= nl2br(e($ms['description'])) ?></p><?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if ($milestones === []): ?>
                    <p class="p-6 text-center text-gray-400">No milestones yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Demand received / pending -->
        <div class="card" data-tabs>
            <div class="border-b border-gray-200 px-6 pt-4">
                <div class="flex gap-6">
                    <button type="button" data-tab-btn="received" class="-mb-px border-b-2 border-brand-600 pb-3 text-sm font-medium text-brand-700">Received (<?= count($received) ?>)</button>
                    <button type="button" data-tab-btn="pending" class="-mb-px border-b-2 border-transparent pb-3 text-sm font-medium text-gray-500 hover:text-gray-700">Pending (<?= count($pending) ?>)</button>
                </div>
            </div>
            <div data-tab-panel="received">
                <?php $renderMemberList($received); ?>
            </div>
            <div data-tab-panel="pending" class="hidden">
                <?php $renderMemberList($pending); ?>
            </div>
        </div>

        <!-- Other income (non-member) -->
        <div class="card">
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                <h2 class="font-semibold text-gray-900">Other income <span class="text-sm font-normal text-gray-400">(non-member)</span></h2>
                <span class="text-sm font-semibold text-brand-700">₹ <?= money($otherIncomeTotal) ?></span>
            </div>
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Date</th><th>Income Head</th><th>Received From</th><th>Mode</th><th>Remarks</th><th class="text-right">Amount</th></tr></thead>
                    <tbody>
                    <?php foreach ($otherIncome as $r): ?>
                        <tr>
                            <td><?= $r['received_on'] ? e(format_date($r['received_on'])) : '—' ?></td>
                            <td><?= e($r['income_head_name'] ?: '—') ?></td>
                            <td><?= e($r['member_name'] ?: '—') ?></td>
                            <td class="capitalize"><?= e(str_replace('_', ' ', $r['mode'])) ?></td>
                            <td class="max-w-xs truncate text-gray-600" title="<?= e($r['remarks'] ?? '') ?>"><?= e($r['remarks'] ?: '—') ?></td>
                            <td class="text-right font-medium text-brand-700">₹ <?= money($r['amount']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($otherIncome === []): ?>
                        <tr><td colspan="6" class="text-center text-gray-400 py-6">No other income recorded. Use <span class="font-medium">Add collection</span> without linking a member demand.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Expenditure -->
        <div class="card">
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                <div class="flex items-center gap-3">
                    <h2 class="font-semibold text-gray-900">Expenditure</h2>
                    <a href="<?= e(url('/expenditures/create?project_id=' . $project['id'])) ?>" class="text-sm text-brand-700 hover:underline">+ Add</a>
                </div>
                <span class="text-sm font-semibold text-red-600">₹ <?= money($spent) ?></span>
            </div>
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Date</th><th>Head</th><th>Category</th><th>Mode</th><th>Remarks</th><th class="text-right">Amount</th></tr></thead>
                    <tbody>
                    <?php foreach ($expenditures as $r): ?>
                        <tr>
                            <td><?= $r['paid_on'] ? e(format_date($r['paid_on'])) : '—' ?></td>
                            <td><?= e($r['head_name'] ?: '—') ?></td>
                            <td class="capitalize"><?= e($r['category']) ?></td>
                            <td class="capitalize"><?= e(str_replace('_', ' ', $r['mode'])) ?></td>
                            <td class="max-w-xs truncate text-gray-600" title="<?= e($r['remarks'] ?? '') ?>"><?= e($r['remarks'] ?: '—') ?></td>
                            <td class="text-right font-medium text-red-600">₹ <?= money($r['amount']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($expenditures === []): ?>
                        <tr><td colspan="6" class="text-center text-gray-400 py-6">No expenditure recorded yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card card-body h-fit">
        <h2 class="font-semibold text-gray-900">Add milestone</h2>
        <form method="post" action="<?= e(url('/projects/' . $project['id'] . '/milestones')) ?>" enctype="multipart/form-data" class="mt-4 space-y-4" novalidate>
            <?= csrf_field() ?>
            <div>
                <label for="title" class="form-label">Title *</label>
                <input type="text" id="title" name="title" value="<?= old('title') ?>" required class="form-input">
                <?php if ($m = error_for('title')): ?><p class="form-error"><?= e($m) ?></p><?php endif; ?>
            </div>
            <div>
                <label for="achieved_on" class="form-label">Achieved on</label>
                <input type="date" id="achieved_on" name="achieved_on" value="<?= old('achieved_on') ?>" class="form-input">
            </div>
            <div>
                <label for="description" class="form-label">Description</label>
                <textarea id="description" name="description" rows="2" class="form-textarea"><?= old('description') ?></textarea>
            </div>
            <div>
                <label for="photo" class="form-label">Photo</label>
                <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-brand-700">
                <?php if ($m = error_for('photo')): ?><p class="form-error"><?= e($m) ?></p><?php endif; ?>
            </div>
            <button type="submit" class="btn-primary w-full">Add milestone</button>
        </form>
    </div>
</div>
