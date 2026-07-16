<?php $this->layout('layouts.app'); /** @var array|null $project */ /** @var list $types */
$isEdit = $project !== null;
$action = $isEdit ? url('/projects/' . $project['id']) : url('/projects');
$val = static fn (string $k, $d = '') => e(old($k) !== '' ? old($k) : ($project[$k] ?? $d));
$selType = static fn ($id) => (string) (old('project_type_id') !== '' ? old('project_type_id') : ($project['project_type_id'] ?? '')) === (string) $id ? 'selected' : '';
$selStatus = static fn ($s) => (string) (old('status') !== '' ? old('status') : ($project['status'] ?? 'active')) === $s ? 'selected' : '';
?>

<h1 class="mb-6 text-2xl font-bold text-gray-900"><?= $isEdit ? 'Edit' : 'New' ?> Project</h1>

<div class="max-w-3xl card card-body">
    <form method="post" action="<?= e($action) ?>" class="space-y-5" novalidate>
        <?= csrf_field() ?>
        <div class="grid gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="name" class="form-label">Project name *</label>
                <input type="text" id="name" name="name" value="<?= $val('name') ?>" required class="form-input">
                <?php if ($m = error_for('name')): ?><p class="form-error"><?= e($m) ?></p><?php endif; ?>
            </div>
            <div>
                <label for="project_type_id" class="form-label">Project type</label>
                <select id="project_type_id" name="project_type_id" class="form-select">
                    <option value="">— Select —</option>
                    <?php foreach ($types as $t): ?>
                        <option value="<?= (int) $t['id'] ?>" <?= $selType($t['id']) ?>><?= e($t['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="status" class="form-label">Status</label>
                <select id="status" name="status" class="form-select">
                    <?php foreach (['planned' => 'Planned', 'active' => 'Active', 'on_hold' => 'On hold', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $k => $v): ?>
                        <option value="<?= $k ?>" <?= $selStatus($k) ?>><?= $v ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="target_amount" class="form-label">Target amount (₹)</label>
                <input type="number" step="0.01" min="0" id="target_amount" name="target_amount" value="<?= $val('target_amount', '0') ?>" class="form-input">
                <?php if ($m = error_for('target_amount')): ?><p class="form-error"><?= e($m) ?></p><?php endif; ?>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="start_date" class="form-label">Start</label>
                    <input type="date" id="start_date" name="start_date" value="<?= $val('start_date') ?>" class="form-input">
                </div>
                <div>
                    <label for="end_date" class="form-label">End</label>
                    <input type="date" id="end_date" name="end_date" value="<?= $val('end_date') ?>" class="form-input">
                </div>
            </div>
            <div class="sm:col-span-2">
                <label for="description" class="form-label">Description</label>
                <textarea id="description" name="description" rows="3" class="form-textarea"><?= $val('description') ?></textarea>
            </div>
        </div>
        <div class="flex gap-2 border-t border-gray-100 pt-4">
            <button type="submit" class="btn-primary"><?= $isEdit ? 'Save changes' : 'Create project' ?></button>
            <a href="<?= e(url($isEdit ? '/projects/' . $project['id'] : '/projects')) ?>" class="btn-secondary">Cancel</a>
        </div>
    </form>
</div>
