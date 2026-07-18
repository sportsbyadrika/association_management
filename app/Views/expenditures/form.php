<?php $this->layout('layouts.app');
/** @var array|null $expenditure */ /** @var list $heads */ /** @var list $projects */ /** @var list $bankAccounts */
/** @var int $selectedProject */ /** @var string $selectedCategory */
$exp = $expenditure ?? null;
$dCategory = (string) ($exp['category'] ?? ($selectedCategory ?? 'association'));
$dMode = (string) ($exp['mode'] ?? 'cash');
$sel = static fn (string $field, $id, $default = 0) => (int) (old($field) !== '' ? old($field) : $default) === (int) $id ? 'selected' : '';
$selCat = static fn ($c) => (string) old('category', $dCategory) === $c ? 'selected' : '';
$selMode = static fn ($m) => (string) old('mode', $dMode) === $m ? 'selected' : '';
$action = $exp ? url('/expenditures/' . $exp['id']) : url('/expenditures');
$heading = $exp ? 'Edit Expenditure' : 'Record Expenditure';
$showProj = (string) old('category', $dCategory) === 'project';
$backProject = $backProject ?? null;
$backUrl = $backProject ? url('/projects/' . $backProject) : url('/expenditures');
?>

<div class="mb-6 flex items-center justify-between">
    <h1 class="text-2xl font-bold text-gray-900"><?= e($heading) ?></h1>
    <?php if ($backProject): ?>
        <a href="<?= e($backUrl) ?>" class="text-sm text-gray-500 hover:text-brand-700">&larr; Back to project</a>
    <?php endif; ?>
</div>

<div class="max-w-2xl card card-body">
    <form method="post" action="<?= e($action) ?>" class="space-y-5" novalidate>
        <?= csrf_field() ?>
        <?php if ($backProject): ?><input type="hidden" name="back_project" value="<?= (int) $backProject ?>"><?php endif; ?>
        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="category" class="form-label">Category *</label>
                <select id="category" name="category" class="form-select" onchange="document.getElementById('projWrap').style.display=this.value==='project'?'block':'none'">
                    <option value="association" <?= $selCat('association') ?>>Association (general)</option>
                    <option value="project" <?= $selCat('project') ?>>Project</option>
                </select>
            </div>
            <div id="projWrap" style="display:<?= $showProj ? 'block' : 'none' ?>">
                <label for="project_id" class="form-label">Project</label>
                <select id="project_id" name="project_id" class="form-select">
                    <option value="">— Select —</option>
                    <?php foreach ($projects as $p): ?>
                        <option value="<?= (int) $p['id'] ?>" <?= $sel('project_id', $p['id'], $selectedProject) ?>><?= e($p['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if ($msg = error_for('project_id')): ?><p class="form-error"><?= e($msg) ?></p><?php endif; ?>
            </div>
            <div>
                <label for="expenditure_head_id" class="form-label">Expenditure head</label>
                <select id="expenditure_head_id" name="expenditure_head_id" class="form-select">
                    <option value="">— Select —</option>
                    <?php foreach ($heads as $h): ?>
                        <option value="<?= (int) $h['id'] ?>" <?= $sel('expenditure_head_id', $h['id'], (int) ($exp['expenditure_head_id'] ?? 0)) ?>><?= e($h['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="amount" class="form-label">Amount (₹) *</label>
                <input type="number" step="0.01" min="0.01" id="amount" name="amount" value="<?= old('amount', $exp['amount'] ?? '') ?>" required class="form-input">
                <?php if ($msg = error_for('amount')): ?><p class="form-error"><?= e($msg) ?></p><?php endif; ?>
            </div>
            <div>
                <label for="paid_on" class="form-label">Paid on *</label>
                <input type="date" id="paid_on" name="paid_on" value="<?= old('paid_on', $exp['paid_on'] ?? date('Y-m-d')) ?>" required class="form-input">
                <?php if ($msg = error_for('paid_on')): ?><p class="form-error"><?= e($msg) ?></p><?php endif; ?>
            </div>
            <div>
                <label for="mode" class="form-label">Payment mode *</label>
                <select id="mode" name="mode" class="form-select">
                    <option value="cash" <?= $selMode('cash') ?>>Cash</option>
                    <option value="fund_transfer" <?= $selMode('fund_transfer') ?>>Fund transfer</option>
                </select>
            </div>
            <div>
                <label for="bank_account_id" class="form-label">Bank account</label>
                <select id="bank_account_id" name="bank_account_id" class="form-select">
                    <option value="">— None —</option>
                    <?php foreach ($bankAccounts as $b): ?>
                        <option value="<?= (int) $b['id'] ?>" <?= $sel('bank_account_id', $b['id'], (int) ($exp['bank_account_id'] ?? 0)) ?>><?= e($b['account_name']) ?> (<?= e($b['type']) ?>)</option>
                    <?php endforeach; ?>
                </select>
                <?php if ($msg = error_for('bank_account_id')): ?><p class="form-error"><?= e($msg) ?></p><?php endif; ?>
            </div>
            <div class="sm:col-span-2">
                <label for="remarks" class="form-label">Remarks</label>
                <input type="text" id="remarks" name="remarks" value="<?= old('remarks', $exp['remarks'] ?? '') ?>" class="form-input">
            </div>
        </div>
        <div class="flex gap-2 border-t border-gray-100 pt-4">
            <button type="submit" class="btn-primary"><?= $exp ? 'Update expenditure' : 'Save expenditure' ?></button>
            <a href="<?= e($backUrl) ?>" class="btn-secondary">Cancel</a>
        </div>
    </form>
</div>
