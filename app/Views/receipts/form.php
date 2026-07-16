<?php $this->layout('layouts.app');
/** @var list $members */ /** @var list $incomeHeads */ /** @var list $projects */ /** @var list $bankAccounts */
$sel = static fn (string $field, $id, $default = 0) => (int) (old($field) !== '' ? old($field) : $default) === (int) $id ? 'selected' : '';
$selMode = static fn ($m) => (string) old('mode', 'cash') === $m ? 'selected' : '';
?>

<h1 class="mb-6 text-2xl font-bold text-gray-900">Record Receipt</h1>

<div class="max-w-2xl card card-body">
    <form method="post" action="<?= e(url('/receipts')) ?>" class="space-y-5" novalidate>
        <?= csrf_field() ?>
        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="member_id" class="form-label">Member</label>
                <select id="member_id" name="member_id" class="form-select">
                    <option value="">— None —</option>
                    <?php foreach ($members as $m): ?>
                        <option value="<?= (int) $m['id'] ?>" <?= $sel('member_id', $m['id'], $selectedMember) ?>><?= e($m['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if ($msg = error_for('member_id')): ?><p class="form-error"><?= e($msg) ?></p><?php endif; ?>
            </div>
            <div>
                <label for="income_head_id" class="form-label">Income head</label>
                <select id="income_head_id" name="income_head_id" class="form-select">
                    <option value="">— Select —</option>
                    <?php foreach ($incomeHeads as $h): ?>
                        <option value="<?= (int) $h['id'] ?>" <?= $sel('income_head_id', $h['id']) ?>><?= e($h['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if ($msg = error_for('income_head_id')): ?><p class="form-error"><?= e($msg) ?></p><?php endif; ?>
            </div>
            <div>
                <label for="project_id" class="form-label">Project (optional)</label>
                <select id="project_id" name="project_id" class="form-select">
                    <option value="">— None (subscription) —</option>
                    <?php foreach ($projects as $p): ?>
                        <option value="<?= (int) $p['id'] ?>" <?= $sel('project_id', $p['id'], $selectedProject) ?>><?= e($p['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="amount" class="form-label">Amount (₹) *</label>
                <input type="number" step="0.01" min="0.01" id="amount" name="amount" value="<?= old('amount') ?>" required class="form-input">
                <?php if ($msg = error_for('amount')): ?><p class="form-error"><?= e($msg) ?></p><?php endif; ?>
            </div>
            <div>
                <label for="mode" class="form-label">Payment mode *</label>
                <select id="mode" name="mode" class="form-select" onchange="document.getElementById('bankWrap').style.opacity = this.value==='fund_transfer'?'1':'0.6'">
                    <option value="cash" <?= $selMode('cash') ?>>Cash</option>
                    <option value="fund_transfer" <?= $selMode('fund_transfer') ?>>Fund transfer</option>
                </select>
            </div>
            <div id="bankWrap">
                <label for="bank_account_id" class="form-label">Bank account</label>
                <select id="bank_account_id" name="bank_account_id" class="form-select">
                    <option value="">— None —</option>
                    <?php foreach ($bankAccounts as $b): ?>
                        <option value="<?= (int) $b['id'] ?>" <?= $sel('bank_account_id', $b['id']) ?>><?= e($b['account_name']) ?> (<?= e($b['type']) ?>)</option>
                    <?php endforeach; ?>
                </select>
                <?php if ($msg = error_for('bank_account_id')): ?><p class="form-error"><?= e($msg) ?></p><?php endif; ?>
            </div>
            <div>
                <label for="received_on" class="form-label">Received on *</label>
                <input type="date" id="received_on" name="received_on" value="<?= old('received_on', date('Y-m-d')) ?>" required class="form-input">
                <?php if ($msg = error_for('received_on')): ?><p class="form-error"><?= e($msg) ?></p><?php endif; ?>
            </div>
            <div class="sm:col-span-2">
                <label for="remarks" class="form-label">Remarks</label>
                <input type="text" id="remarks" name="remarks" value="<?= old('remarks') ?>" class="form-input">
            </div>
        </div>
        <div class="flex gap-2 border-t border-gray-100 pt-4">
            <button type="submit" class="btn-primary">Save receipt</button>
            <a href="<?= e(url('/receipts')) ?>" class="btn-secondary">Cancel</a>
        </div>
    </form>
</div>
