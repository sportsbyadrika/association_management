<?php $this->layout('layouts.app');
/** @var array $details */ /** @var list $members */ /** @var ?string $projectName */
$count = count($members);
$each = (float) $details['amount'];
$total = $each * $count;
$purposeLabel = ['subscription' => 'Subscription', 'project' => 'Project contribution', 'other' => 'Other'][$details['purpose']] ?? $details['purpose'];
?>

<div class="mb-6">
    <a href="<?= e(url('/demands/create')) ?>" class="text-sm text-gray-500 hover:text-brand-700">&larr; Back to edit</a>
    <h1 class="mt-1 text-2xl font-bold text-gray-900">Confirm Demands</h1>
    <p class="mt-1 text-sm text-gray-500">Review the details and the members below. One demand will be created per member.</p>
</div>

<div class="card card-body">
    <?php $steps = ['Details & members', 'Confirm', 'Done']; $active = 1; include dirname(__DIR__) . '/partials/wizard_steps.php'; ?>

    <!-- Summary -->
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-lg bg-gray-50 p-4">
            <p class="text-xs uppercase tracking-wide text-gray-500">Purpose</p>
            <p class="mt-1 font-semibold text-gray-900"><?= e($purposeLabel) ?><?= $projectName ? ' · ' . e($projectName) : '' ?></p>
        </div>
        <div class="rounded-lg bg-gray-50 p-4">
            <p class="text-xs uppercase tracking-wide text-gray-500">Amount / member</p>
            <p class="mt-1 font-semibold text-brand-700">₹ <?= money($each) ?></p>
        </div>
        <div class="rounded-lg bg-gray-50 p-4">
            <p class="text-xs uppercase tracking-wide text-gray-500">Members</p>
            <p class="mt-1 font-semibold text-gray-900"><?= $count ?></p>
        </div>
        <div class="rounded-lg bg-gray-50 p-4">
            <p class="text-xs uppercase tracking-wide text-gray-500">Total to be demanded</p>
            <p class="mt-1 font-semibold text-gray-900">₹ <?= money($total) ?></p>
        </div>
    </div>
    <div class="mt-3 flex flex-wrap gap-x-8 gap-y-1 text-sm text-gray-600">
        <span>Due date: <span class="font-medium text-gray-800"><?= e(format_date($details['due_date'] ?: null)) ?></span></span>
        <?php if (!empty($details['remarks'])): ?><span>Remarks: <span class="font-medium text-gray-800"><?= e($details['remarks']) ?></span></span><?php endif; ?>
    </div>

    <!-- Member list -->
    <div class="mt-6 overflow-x-auto rounded-lg ring-1 ring-gray-200">
        <table class="table">
            <thead><tr><th>#</th><th>Member No.</th><th>Name</th><th>Mobile</th><th class="text-right">Amount</th></tr></thead>
            <tbody>
            <?php foreach ($members as $i => $m): ?>
                <tr>
                    <td class="text-gray-400"><?= $i + 1 ?></td>
                    <td class="font-medium text-gray-700"><?= e($m['member_number'] ?? '—') ?></td>
                    <td><?= e($m['name']) ?></td>
                    <td><?= e($m['mobile'] ?? '—') ?></td>
                    <td class="text-right">₹ <?= money($each) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="bg-gray-50 font-semibold">
                    <td colspan="4" class="text-right">Total</td>
                    <td class="text-right">₹ <?= money($total) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <form method="post" action="<?= e(url('/demands/bulk')) ?>" class="mt-6 flex items-center gap-2 border-t border-gray-100 pt-5">
        <?= csrf_field() ?>
        <input type="hidden" name="purpose" value="<?= e($details['purpose']) ?>">
        <?php if ($details['purpose'] === 'project'): ?>
            <input type="hidden" name="project_id" value="<?= e((string) $details['project_id']) ?>">
        <?php endif; ?>
        <input type="hidden" name="amount" value="<?= e($details['amount']) ?>">
        <input type="hidden" name="due_date" value="<?= e($details['due_date']) ?>">
        <input type="hidden" name="remarks" value="<?= e($details['remarks']) ?>">
        <?php foreach ($members as $m): ?>
            <input type="hidden" name="member_ids[]" value="<?= (int) $m['id'] ?>">
        <?php endforeach; ?>

        <button type="submit" class="btn-primary">Confirm &amp; raise <?= $count ?> demand<?= $count === 1 ? '' : 's' ?></button>
        <a href="<?= e(url('/demands/create')) ?>" class="btn-secondary">Cancel</a>
    </form>
</div>
