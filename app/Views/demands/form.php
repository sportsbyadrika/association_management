<?php $this->layout('layouts.app');
/** @var list $members */ /** @var list $projects */ /** @var list $preselected */ /** @var array $existingDemands */
$selP = static fn ($id) => (string) old('purpose', 'subscription') === $id ? 'selected' : '';
$existingJson = json_encode($existingDemands ?? [], JSON_UNESCAPED_SLASHES);
?>

<div class="mb-6">
    <a href="<?= e(url('/demands')) ?>" class="text-sm text-gray-500 hover:text-brand-700">&larr; Back to demands</a>
    <h1 class="mt-1 text-2xl font-bold text-gray-900">Raise Demand</h1>
    <p class="mt-1 text-sm text-gray-500">Set the demand details, pick one or more members, then review before saving.</p>
</div>

<div class="card card-body">
    <?php $steps = ['Details & members', 'Confirm', 'Done']; $active = 0; include dirname(__DIR__) . '/partials/wizard_steps.php'; ?>

    <form method="post" action="<?= e(url('/demands/preview')) ?>" novalidate>
        <?= csrf_field() ?>
        <div class="grid gap-6 lg:grid-cols-5">

            <!-- Left: demand details -->
            <div class="lg:col-span-2 space-y-5">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Demand details</h2>
                <div>
                    <label for="purpose" class="form-label">Purpose *</label>
                    <select id="purpose" name="purpose" class="form-select" onchange="document.getElementById('projectWrap').style.display=this.value==='project'?'block':'none';document.getElementById('project_id').required=this.value==='project';">
                        <option value="subscription" <?= $selP('subscription') ?>>Subscription</option>
                        <option value="project" <?= $selP('project') ?>>Project contribution</option>
                        <option value="other" <?= $selP('other') ?>>Other</option>
                    </select>
                </div>
                <div id="projectWrap" style="display:<?= old('purpose') === 'project' ? 'block' : 'none' ?>">
                    <label for="project_id" class="form-label">Project</label>
                    <select id="project_id" name="project_id" class="form-select">
                        <option value="">— Select —</option>
                        <?php foreach ($projects as $p): ?>
                            <option value="<?= (int) $p['id'] ?>" <?= (string) old('project_id') === (string) $p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($m = error_for('project_id')): ?><p class="form-error"><?= e($m) ?></p><?php endif; ?>
                </div>
                <div>
                    <label for="amount" class="form-label">Amount per member (₹) *</label>
                    <input type="number" step="0.01" min="0.01" id="amount" name="amount" value="<?= old('amount') ?>" required class="form-input">
                    <?php if ($m = error_for('amount')): ?><p class="form-error"><?= e($m) ?></p><?php endif; ?>
                    <p class="mt-1 text-xs text-gray-400">The same amount is charged to every selected member.</p>
                </div>
                <div>
                    <label for="due_date" class="form-label">Due date</label>
                    <input type="date" id="due_date" name="due_date" value="<?= old('due_date') ?>" class="form-input">
                </div>
                <div>
                    <label for="remarks" class="form-label">Remarks</label>
                    <input type="text" id="remarks" name="remarks" value="<?= old('remarks') ?>" maxlength="500" class="form-input">
                </div>
            </div>

            <!-- Right: member selection -->
            <div class="lg:col-span-3" data-member-select data-existing-demands='<?= e($existingJson) ?>'>
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Select members</h2>
                    <span class="text-sm text-gray-500"><span data-selected-count>0</span> selected</span>
                </div>

                <div data-exclude-wrap class="mb-3 rounded-lg bg-amber-50 px-3 py-2" style="display:<?= old('purpose') === 'project' ? 'block' : 'none' ?>">
                    <label class="flex items-center gap-2 text-sm text-amber-800">
                        <input type="checkbox" data-exclude-existing class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                        Exclude members who already have a demand for the selected project
                        <span data-excluded-count class="ml-1 font-medium"></span>
                    </label>
                </div>

                <input type="text" data-member-filter placeholder="Search by name, member number or mobile…" class="form-input mb-3">

                <div class="overflow-hidden rounded-lg ring-1 ring-gray-200">
                    <div class="max-h-96 overflow-y-auto">
                        <table class="table">
                            <thead class="sticky top-0">
                                <tr>
                                    <th class="w-10"><input type="checkbox" data-select-all class="rounded border-gray-300 text-brand-600 focus:ring-brand-500"></th>
                                    <th>Member No.</th><th>Name</th><th>Mobile</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($members as $m): ?>
                                <?php $search = strtolower(trim(($m['member_number'] ?? '') . ' ' . $m['name'] . ' ' . ($m['mobile'] ?? ''))); ?>
                                <tr data-row data-search="<?= e($search) ?>">
                                    <td><input type="checkbox" name="member_ids[]" value="<?= (int) $m['id'] ?>" data-member-cb <?= in_array((int) $m['id'], $preselected, true) ? 'checked' : '' ?> class="rounded border-gray-300 text-brand-600 focus:ring-brand-500"></td>
                                    <td class="font-medium text-gray-700"><?= e($m['member_number'] ?? '—') ?></td>
                                    <td><?= e($m['name']) ?></td>
                                    <td><?= e($m['mobile'] ?? '—') ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if ($members === []): ?>
                                <tr><td colspan="4" class="text-center text-gray-400 py-8">No active members. Add members first.</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div data-member-empty class="hidden px-4 py-6 text-center text-sm text-gray-400">No members match your search.</div>
                </div>
                <p class="mt-2 text-xs text-gray-400">Tip: search then use the header checkbox to select all matching members.</p>
            </div>
        </div>

        <div class="mt-6 flex items-center gap-2 border-t border-gray-100 pt-5">
            <button type="submit" class="btn-primary">Review demands &rarr;</button>
            <a href="<?= e(url('/demands')) ?>" class="btn-secondary">Cancel</a>
        </div>
    </form>
</div>
