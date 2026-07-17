<?php $this->layout('layouts.app'); /** @var array|null $member */ /** @var list $memberTypes */
$isEdit = $member !== null;
$action = $isEdit ? url('/members/' . $member['id']) : url('/members');
$val = static fn (string $k, $d = '') => e(old($k) !== '' ? old($k) : ($member[$k] ?? $d));
$selType = static fn ($id) => (string) (old('member_type_id') !== '' ? old('member_type_id') : ($member['member_type_id'] ?? '')) === (string) $id ? 'selected' : '';
$selGender = static fn ($g) => (string) (old('gender') !== '' ? old('gender') : ($member['gender'] ?? '')) === $g ? 'selected' : '';
?>

<h1 class="mb-6 text-2xl font-bold text-gray-900"><?= $isEdit ? 'Edit' : 'Add' ?> Member</h1>

<div class="max-w-4xl card card-body">
    <form method="post" action="<?= e($action) ?>" enctype="multipart/form-data" class="space-y-5" novalidate>
        <?= csrf_field() ?>
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <label for="member_number" class="form-label">Member number *</label>
                <input type="text" id="member_number" name="member_number" value="<?= $val('member_number') ?>" required autofocus maxlength="50" class="form-input">
                <?php if ($m = error_for('member_number')): ?><p class="form-error"><?= e($m) ?></p><?php endif; ?>
            </div>
            <div class="lg:col-span-2">
                <label for="name" class="form-label">Full name *</label>
                <input type="text" id="name" name="name" value="<?= $val('name') ?>" required class="form-input">
                <?php if ($m = error_for('name')): ?><p class="form-error"><?= e($m) ?></p><?php endif; ?>
            </div>
            <div>
                <label for="member_type_id" class="form-label">Member type</label>
                <select id="member_type_id" name="member_type_id" class="form-select">
                    <option value="">— Select —</option>
                    <?php foreach ($memberTypes as $t): ?>
                        <option value="<?= (int) $t['id'] ?>" <?= $selType($t['id']) ?>><?= e($t['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="age" class="form-label">Age</label>
                <input type="number" id="age" name="age" min="0" max="150" value="<?= $val('age') ?>" class="form-input">
                <?php if ($m = error_for('age')): ?><p class="form-error"><?= e($m) ?></p><?php endif; ?>
            </div>
            <div>
                <label for="gender" class="form-label">Gender</label>
                <select id="gender" name="gender" class="form-select">
                    <option value="">— Select —</option>
                    <option value="male" <?= $selGender('male') ?>>Male</option>
                    <option value="female" <?= $selGender('female') ?>>Female</option>
                    <option value="other" <?= $selGender('other') ?>>Other</option>
                </select>
            </div>
            <div>
                <label for="family_members_count" class="form-label"># Family members</label>
                <input type="number" id="family_members_count" name="family_members_count" min="0" max="100" value="<?= $val('family_members_count') ?>" class="form-input">
                <?php if ($m = error_for('family_members_count')): ?><p class="form-error"><?= e($m) ?></p><?php endif; ?>
            </div>
            <div>
                <label for="mobile" class="form-label">Mobile</label>
                <input type="text" id="mobile" name="mobile" value="<?= $val('mobile') ?>" class="form-input">
                <?php if ($m = error_for('mobile')): ?><p class="form-error"><?= e($m) ?></p><?php endif; ?>
            </div>
            <div>
                <label for="whatsapp" class="form-label">WhatsApp</label>
                <input type="text" id="whatsapp" name="whatsapp" value="<?= $val('whatsapp') ?>" class="form-input">
                <?php if ($m = error_for('whatsapp')): ?><p class="form-error"><?= e($m) ?></p><?php endif; ?>
            </div>
            <div>
                <label for="email" class="form-label">Email</label>
                <input type="email" id="email" name="email" value="<?= $val('email') ?>" class="form-input">
                <?php if ($m = error_for('email')): ?><p class="form-error"><?= e($m) ?></p><?php endif; ?>
            </div>
            <div>
                <label for="occupation" class="form-label">Occupation</label>
                <input type="text" id="occupation" name="occupation" value="<?= $val('occupation') ?>" class="form-input">
            </div>
            <div>
                <label for="joined_on" class="form-label">Joined on</label>
                <input type="date" id="joined_on" name="joined_on" value="<?= $val('joined_on') ?>" class="form-input">
            </div>
            <div class="sm:col-span-2 lg:col-span-3">
                <label for="address" class="form-label">Address</label>
                <textarea id="address" name="address" rows="2" class="form-textarea"><?= $val('address') ?></textarea>
            </div>
            <div class="sm:col-span-2 lg:col-span-2">
                <label for="notes" class="form-label">Other details / notes</label>
                <textarea id="notes" name="notes" rows="2" class="form-textarea"><?= $val('notes') ?></textarea>
            </div>
            <div>
                <label for="photo" class="form-label">Photo</label>
                <?php if ($isEdit && !empty($member['photo_path'])): ?>
                    <img src="<?= e(url('/photo/member/' . $member['id'])) ?>" alt="" class="mb-2 h-20 w-20 rounded-lg object-cover ring-1 ring-gray-200">
                <?php endif; ?>
                <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/webp" data-crop="passport" class="block w-full text-sm text-gray-600 file:mr-4 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-brand-700">
                <?php if ($m = error_for('photo')): ?><p class="form-error"><?= e($m) ?></p><?php endif; ?>
                <p class="mt-1 text-xs text-gray-400">JPEG/PNG/WebP, max 3&nbsp;MB. You can crop to passport size after choosing.</p>
            </div>
        </div>
        <div class="flex gap-2 border-t border-gray-100 pt-4">
            <button type="submit" class="btn-primary"><?= $isEdit ? 'Save changes' : 'Add member' ?></button>
            <a href="<?= e(url($isEdit ? '/members/' . $member['id'] : '/members')) ?>" class="btn-secondary">Cancel</a>
            <?php if ($isEdit): ?>
                <form method="post" action="<?= e(url('/members/' . $member['id'] . '/delete')) ?>" class="ml-auto" data-confirm="Deactivate this member? Their financial history is preserved.">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn-danger">Deactivate</button>
                </form>
            <?php endif; ?>
        </div>
    </form>
</div>
