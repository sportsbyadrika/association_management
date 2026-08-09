<?php $this->layout('layouts.app');
/** @var array $committee */ /** @var array|null $official */ /** @var list $designations */ /** @var array|null $login */
$o = $official ?? null;
$login = $login ?? null;
$isEdit = $o !== null;
$action = $isEdit ? url('/officials/' . $o['id']) : url('/committees/' . $committee['id'] . '/officials');
$val = static fn (string $k, $d = '') => e(old($k) !== '' ? old($k) : ($o[$k] ?? $d));
$selDes = static fn ($id) => (string) (old('official_designation_id') !== '' ? old('official_designation_id') : ($o['official_designation_id'] ?? '')) === (string) $id ? 'selected' : '';
$loginRole = (string) (old('login_role') !== '' ? old('login_role') : ($login['role'] ?? 'association_staff'));
?>

<div class="mb-6 flex items-center justify-between">
    <h1 class="text-2xl font-bold text-gray-900"><?= $isEdit ? 'Edit' : 'Add' ?> Official</h1>
    <a href="<?= e(url('/committees/' . $committee['id'])) ?>" class="text-sm text-gray-500 hover:text-brand-700">&larr; <?= e($committee['name']) ?></a>
</div>

<div class="max-w-3xl card card-body">
    <form method="post" action="<?= e($action) ?>" enctype="multipart/form-data" class="space-y-5" novalidate>
        <?= csrf_field() ?>
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <label for="official_designation_id" class="form-label">Designation</label>
                <select id="official_designation_id" name="official_designation_id" class="form-select">
                    <option value="">— Select —</option>
                    <?php foreach ($designations as $d): ?>
                        <option value="<?= (int) $d['id'] ?>" <?= $selDes($d['id']) ?>><?= e($d['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if ($m = error_for('official_designation_id')): ?><p class="form-error"><?= e($m) ?></p><?php endif; ?>
            </div>
            <div class="lg:col-span-2">
                <label for="name" class="form-label">Member name *</label>
                <input type="text" id="name" name="name" value="<?= $val('name') ?>" required class="form-input">
                <?php if ($m = error_for('name')): ?><p class="form-error"><?= e($m) ?></p><?php endif; ?>
            </div>
            <div>
                <label for="phone" class="form-label">Phone number</label>
                <input type="text" id="phone" name="phone" value="<?= $val('phone') ?>" class="form-input">
                <?php if ($m = error_for('phone')): ?><p class="form-error"><?= e($m) ?></p><?php endif; ?>
            </div>
            <div class="lg:col-span-2">
                <label for="email" class="form-label">Email id</label>
                <input type="email" id="email" name="email" value="<?= $val('email') ?>" class="form-input">
                <?php if ($m = error_for('email')): ?><p class="form-error"><?= e($m) ?></p><?php endif; ?>
            </div>
            <div class="sm:col-span-2 lg:col-span-2">
                <label for="address" class="form-label">Address</label>
                <textarea id="address" name="address" rows="2" class="form-textarea"><?= $val('address') ?></textarea>
                <?php if ($m = error_for('address')): ?><p class="form-error"><?= e($m) ?></p><?php endif; ?>
            </div>
            <div>
                <label for="photo" class="form-label">Photo</label>
                <?php if ($isEdit && !empty($o['photo_path'])): ?>
                    <img src="<?= e(url('/photo/official/' . $o['id'])) ?>" alt="" class="mb-2 h-20 w-20 rounded-lg object-cover ring-1 ring-gray-200">
                <?php endif; ?>
                <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/webp" data-crop="passport" class="block w-full text-sm text-gray-600 file:mr-4 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-brand-700">
                <?php if ($m = error_for('photo')): ?><p class="form-error"><?= e($m) ?></p><?php endif; ?>
            </div>
        </div>

        <!-- Login -->
        <div class="border-t border-gray-100 pt-5">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Login</h2>
            <p class="mt-1 text-xs text-gray-400">Official logins can access the Dashboard and Reports only.</p>
            <?php if ($login !== null): ?>
                <p class="mt-2 text-sm text-gray-600">This official has a login: <span class="font-medium text-gray-900"><?= e($login['email']) ?></span></p>
                <div class="mt-3 max-w-sm">
                    <label for="login_password" class="form-label">Reset password (optional)</label>
                    <input type="text" id="login_password" name="login_password" value="" class="form-input" placeholder="Leave blank to keep current">
                    <?php if ($m = error_for('login_password')): ?><p class="form-error"><?= e($m) ?></p><?php endif; ?>
                </div>
            <?php else: ?>
                <label class="mt-2 inline-flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="create_login" value="1" <?= old('create_login') !== '' ? 'checked' : '' ?> class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                    Create a login for this official (uses the email above)
                </label>
                <div class="mt-3 max-w-sm">
                    <label for="login_password" class="form-label">Temporary password</label>
                    <input type="text" id="login_password" name="login_password" value="<?= old('login_password') ?>" class="form-input" placeholder="Min 8 characters">
                    <?php if ($m = error_for('login_password')): ?><p class="form-error"><?= e($m) ?></p><?php endif; ?>
                </div>
                <p class="mt-1 text-xs text-gray-400">The official must change this password on first login.</p>
            <?php endif; ?>
        </div>

        <div class="flex gap-2 border-t border-gray-100 pt-4">
            <button type="submit" class="btn-primary"><?= $isEdit ? 'Save changes' : 'Add official' ?></button>
            <a href="<?= e(url('/committees/' . $committee['id'])) ?>" class="btn-secondary">Cancel</a>
            <?php if ($isEdit): ?>
                <form method="post" action="<?= e(url('/officials/' . $o['id'] . '/delete')) ?>" class="ml-auto" data-confirm="Remove this official?">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn-danger">Remove</button>
                </form>
            <?php endif; ?>
        </div>
    </form>
</div>
