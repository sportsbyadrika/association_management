<?php $this->layout('layouts.app'); /** @var array $association */
$val = static fn (string $k, $d = '') => e(old($k) !== '' ? old($k) : ($association[$k] ?? $d));
?>

<h1 class="mb-1 text-2xl font-bold text-gray-900">Subscription</h1>
<p class="mb-6 text-sm text-gray-500"><?= e($association['name']) ?></p>

<div class="max-w-xl card card-body">
    <form method="post" action="<?= e(url('/admin/associations/' . $association['id'] . '/subscription')) ?>" class="space-y-5" novalidate>
        <?= csrf_field() ?>
        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="subscription_start" class="form-label">Start date</label>
                <input type="date" id="subscription_start" name="subscription_start" value="<?= $val('subscription_start') ?>" class="form-input">
            </div>
            <div>
                <label for="subscription_end" class="form-label">End date</label>
                <input type="date" id="subscription_end" name="subscription_end" value="<?= $val('subscription_end') ?>" class="form-input">
                <?php if ($m = error_for('subscription_end')): ?><p class="form-error"><?= e($m) ?></p><?php endif; ?>
            </div>
        </div>
        <label class="inline-flex items-center gap-2">
            <input type="checkbox" name="is_active" value="1" <?= (old('is_active') !== '' ? old('is_active') : ($association['is_active'] ?? 1)) ? 'checked' : '' ?> class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
            <span class="text-sm text-gray-700">Association is active (users can log in)</span>
        </label>
        <p class="rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-700">When the subscription end date passes or the association is marked inactive, its users are blocked from signing in.</p>
        <div class="flex gap-2 border-t border-gray-100 pt-4">
            <button type="submit" class="btn-primary">Update subscription</button>
            <a href="<?= e(url('/admin/associations')) ?>" class="btn-secondary">Cancel</a>
        </div>
    </form>
</div>
