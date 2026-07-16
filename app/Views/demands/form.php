<?php $this->layout('layouts.app'); /** @var list $members */ /** @var list $projects */ /** @var int $selectedMember */
$selP = static fn ($id) => (string) old('purpose', 'subscription') === $id ? 'selected' : '';
?>

<h1 class="mb-6 text-2xl font-bold text-gray-900">Raise Demand</h1>

<div class="max-w-2xl card card-body">
    <form method="post" action="<?= e(url('/demands')) ?>" class="space-y-5" novalidate>
        <?= csrf_field() ?>
        <div class="grid gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="member_id" class="form-label">Member *</label>
                <select id="member_id" name="member_id" required class="form-select">
                    <option value="">— Select member —</option>
                    <?php foreach ($members as $m): ?>
                        <option value="<?= (int) $m['id'] ?>" <?= (int) (old('member_id') ?: $selectedMember) === (int) $m['id'] ? 'selected' : '' ?>><?= e($m['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if ($msg = error_for('member_id')): ?><p class="form-error"><?= e($msg) ?></p><?php endif; ?>
            </div>
            <div>
                <label for="purpose" class="form-label">Purpose *</label>
                <select id="purpose" name="purpose" class="form-select" onchange="document.getElementById('projectWrap').style.display = this.value==='project' ? 'block':'none'">
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
            </div>
            <div>
                <label for="amount" class="form-label">Amount (₹) *</label>
                <input type="number" step="0.01" min="0.01" id="amount" name="amount" value="<?= old('amount') ?>" required class="form-input">
                <?php if ($msg = error_for('amount')): ?><p class="form-error"><?= e($msg) ?></p><?php endif; ?>
            </div>
            <div>
                <label for="due_date" class="form-label">Due date</label>
                <input type="date" id="due_date" name="due_date" value="<?= old('due_date') ?>" class="form-input">
            </div>
            <div class="sm:col-span-2">
                <label for="remarks" class="form-label">Remarks</label>
                <input type="text" id="remarks" name="remarks" value="<?= old('remarks') ?>" class="form-input">
            </div>
        </div>
        <div class="flex gap-2 border-t border-gray-100 pt-4">
            <button type="submit" class="btn-primary">Raise demand</button>
            <a href="<?= e(url('/demands')) ?>" class="btn-secondary">Cancel</a>
        </div>
    </form>
</div>
