<?php $this->layout('layouts.app'); /** @var array|null $account */
$isEdit = $account !== null;
$action = $isEdit ? url('/bank-accounts/' . $account['id']) : url('/bank-accounts');
$val = static fn (string $k, $d = '') => e(old($k) !== '' ? old($k) : ($account[$k] ?? $d));
$selType = static fn ($t) => (string) (old('type') !== '' ? old('type') : ($account['type'] ?? 'association')) === $t ? 'selected' : '';
?>

<h1 class="mb-6 text-2xl font-bold text-gray-900"><?= $isEdit ? 'Edit' : 'Add' ?> Bank Account</h1>

<div class="max-w-2xl card card-body">
    <form method="post" action="<?= e($action) ?>" class="space-y-5" novalidate>
        <?= csrf_field() ?>
        <div class="grid gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="account_name" class="form-label">Account name *</label>
                <input type="text" id="account_name" name="account_name" value="<?= $val('account_name') ?>" required class="form-input">
                <?php if ($m = error_for('account_name')): ?><p class="form-error"><?= e($m) ?></p><?php endif; ?>
            </div>
            <div>
                <label for="type" class="form-label">Type</label>
                <select id="type" name="type" class="form-select">
                    <option value="association" <?= $selType('association') ?>>Association</option>
                    <option value="treasurer" <?= $selType('treasurer') ?>>Treasurer</option>
                </select>
            </div>
            <div>
                <label for="account_number_masked" class="form-label">Account number (masked)</label>
                <input type="text" id="account_number_masked" name="account_number_masked" value="<?= $val('account_number_masked') ?>" placeholder="e.g. XXXX1234" class="form-input">
            </div>
            <div>
                <label for="opening_balance" class="form-label">Opening balance (₹)</label>
                <input type="number" step="0.01" id="opening_balance" name="opening_balance" value="<?= $val('opening_balance', '0') ?>" <?= $isEdit ? '' : '' ?> class="form-input">
                <?php if ($m = error_for('opening_balance')): ?><p class="form-error"><?= e($m) ?></p><?php endif; ?>
            </div>
            <div class="sm:col-span-2">
                <label for="description" class="form-label">Description</label>
                <input type="text" id="description" name="description" value="<?= $val('description') ?>" class="form-input">
            </div>
            <div class="sm:col-span-2">
                <label class="inline-flex items-center gap-2">
                    <input type="checkbox" name="is_active" value="1" <?= (old('is_active') !== '' ? old('is_active') : ($account['is_active'] ?? 1)) ? 'checked' : '' ?> class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                    <span class="text-sm text-gray-700">Active</span>
                </label>
            </div>
        </div>
        <div class="flex gap-2 border-t border-gray-100 pt-4">
            <button type="submit" class="btn-primary"><?= $isEdit ? 'Save changes' : 'Add account' ?></button>
            <a href="<?= e(url('/bank-accounts')) ?>" class="btn-secondary">Cancel</a>
        </div>
    </form>
</div>
