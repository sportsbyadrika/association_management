<?php $this->layout('layouts.app');
/** @var array $details */ /** @var list $members */ /** @var ?string $projectName */
/** @var array $memberAmounts */ /** @var list $invalidIds */ /** @var ?string $error */
$count = count($members);
$defaultEach = (float) $details['amount'];
$initialTotal = 0.0;
foreach ($members as $m) {
    $initialTotal += (float) ($memberAmounts[(int) $m['id']] ?? $defaultEach);
}
$invalidIds = $invalidIds ?? [];
$purposeLabel = ['subscription' => 'Subscription', 'project' => 'Project contribution', 'other' => 'Other'][$details['purpose']] ?? $details['purpose'];
?>

<div class="mb-6">
    <a href="<?= e(url('/demands/create')) ?>" class="text-sm text-gray-500 hover:text-brand-700">&larr; Back to edit</a>
    <h1 class="mt-1 text-2xl font-bold text-gray-900">Confirm Demands</h1>
    <p class="mt-1 text-sm text-gray-500">Review the details. You can fine-tune any member's amount before confirming — one demand is created per member.</p>
</div>

<div class="card card-body">
    <?php $steps = ['Details & members', 'Confirm', 'Done']; $active = 1; include dirname(__DIR__) . '/partials/wizard_steps.php'; ?>

    <?php if (!empty($error)): ?>
        <div class="mb-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-800 ring-1 ring-red-200"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= e(url('/demands/bulk')) ?>" data-amount-sum novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="purpose" value="<?= e($details['purpose']) ?>">
        <?php if ($details['purpose'] === 'project'): ?>
            <input type="hidden" name="project_id" value="<?= e((string) $details['project_id']) ?>">
        <?php endif; ?>
        <input type="hidden" name="amount" value="<?= e($details['amount']) ?>">
        <input type="hidden" name="due_date" value="<?= e($details['due_date']) ?>">
        <input type="hidden" name="remarks" value="<?= e($details['remarks']) ?>">

        <!-- Summary -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-lg bg-gray-50 p-4">
                <p class="text-xs uppercase tracking-wide text-gray-500">Purpose</p>
                <p class="mt-1 font-semibold text-gray-900"><?= e($purposeLabel) ?><?= $projectName ? ' · ' . e($projectName) : '' ?></p>
            </div>
            <div class="rounded-lg bg-gray-50 p-4">
                <p class="text-xs uppercase tracking-wide text-gray-500">Default amount</p>
                <p class="mt-1 font-semibold text-brand-700">₹ <?= money($defaultEach) ?></p>
            </div>
            <div class="rounded-lg bg-gray-50 p-4">
                <p class="text-xs uppercase tracking-wide text-gray-500">Members</p>
                <p class="mt-1 font-semibold text-gray-900"><?= $count ?></p>
            </div>
            <div class="rounded-lg bg-gray-50 p-4">
                <p class="text-xs uppercase tracking-wide text-gray-500">Total to be demanded</p>
                <p class="mt-1 font-semibold text-gray-900">₹ <span data-amount-total><?= money($initialTotal) ?></span></p>
            </div>
        </div>
        <div class="mt-3 flex flex-wrap gap-x-8 gap-y-1 text-sm text-gray-600">
            <span>Due date: <span class="font-medium text-gray-800"><?= e(format_date($details['due_date'] ?: null)) ?></span></span>
            <?php if (!empty($details['remarks'])): ?><span>Remarks: <span class="font-medium text-gray-800"><?= e($details['remarks']) ?></span></span><?php endif; ?>
        </div>

        <!-- Member list with editable amounts -->
        <div class="mt-6 overflow-x-auto rounded-lg ring-1 ring-gray-200">
            <table class="table">
                <thead><tr><th>#</th><th>Member No.</th><th>Name</th><th>Mobile</th><th class="w-40 text-right">Amount (₹)</th></tr></thead>
                <tbody>
                <?php foreach ($members as $i => $m): ?>
                    <?php $id = (int) $m['id']; $amt = $memberAmounts[$id] ?? $details['amount']; $bad = in_array($id, $invalidIds, true); ?>
                    <tr class="<?= $bad ? 'bg-red-50' : '' ?>">
                        <td class="text-gray-400"><?= $i + 1 ?></td>
                        <td class="font-medium text-gray-700"><?= e($m['member_number'] ?? '—') ?></td>
                        <td><?= e($m['name']) ?></td>
                        <td><?= e($m['mobile'] ?? '—') ?></td>
                        <td class="text-right">
                            <input type="hidden" name="member_ids[]" value="<?= $id ?>">
                            <input type="number" step="0.01" min="0.01" name="amounts[<?= $id ?>]" value="<?= e($amt) ?>"
                                   data-amount-input
                                   class="form-input w-32 text-right <?= $bad ? 'border-red-400' : '' ?>">
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="bg-gray-50 font-semibold">
                        <td colspan="4" class="text-right">Total</td>
                        <td class="text-right">₹ <span data-amount-total><?= money($initialTotal) ?></span></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="mt-6 flex items-center gap-2 border-t border-gray-100 pt-5">
            <button type="submit" class="btn-primary">Confirm &amp; raise <?= $count ?> demand<?= $count === 1 ? '' : 's' ?></button>
            <a href="<?= e(url('/demands/create')) ?>" class="btn-secondary">Cancel</a>
        </div>
    </form>
</div>
