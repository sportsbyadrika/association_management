<?php $this->layout('layouts.app');
/** @var list $members */ /** @var list $memberTypes */ /** @var list $projects */ /** @var list $gifts */ /** @var list $events */
/** @var list $preselected */ /** @var array $existingDemands */
/** @var string $presetCategory */ /** @var int $presetProject */ /** @var int $presetGift */ /** @var int $presetEvent */
$presetCategory = $presetCategory ?? 'subscription';
$curCat = old('category', $presetCategory);
$curProject = old('project_id', (string) ($presetProject ?? 0));
$curGift = old('gift_id', (string) ($presetGift ?? 0));
$curEvent = old('event_id', (string) ($presetEvent ?? 0));
$existingJson = json_encode($existingDemands ?? [], JSON_UNESCAPED_SLASHES);
$catWrap = static fn (string $c) => 'display:' . ($curCat === $c ? 'block' : 'none');
?>

<div class="mb-6">
    <a href="<?= e(url('/demands')) ?>" class="text-sm text-gray-500 hover:text-brand-700">&larr; Back to dues</a>
    <h1 class="mt-1 text-2xl font-bold text-gray-900">Raise Due</h1>
    <p class="mt-1 text-sm text-gray-500">Set the due details, pick one or more members, then review before saving.</p>
</div>

<div class="card card-body">
    <?php $steps = ['Details & members', 'Confirm', 'Done']; $active = 0; include dirname(__DIR__) . '/partials/wizard_steps.php'; ?>

    <form method="post" action="<?= e(url('/demands/preview')) ?>" novalidate>
        <?= csrf_field() ?>
        <div class="grid gap-6 lg:grid-cols-5">

            <!-- Left: due details -->
            <div class="lg:col-span-2 space-y-5">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Due details</h2>
                <div>
                    <label for="category" class="form-label">Due for *</label>
                    <select id="category" name="category" required class="form-select" data-category-select>
                        <option value="subscription" <?= $curCat === 'subscription' ? 'selected' : '' ?>>Subscription</option>
                        <option value="project" <?= $curCat === 'project' ? 'selected' : '' ?>>Project</option>
                        <option value="gift" <?= $curCat === 'gift' ? 'selected' : '' ?>>Gift</option>
                        <option value="event" <?= $curCat === 'event' ? 'selected' : '' ?>>Event</option>
                    </select>
                    <?php if ($m = error_for('category')): ?><p class="form-error"><?= e($m) ?></p><?php endif; ?>
                </div>

                <div data-cat-wrap="project" style="<?= $catWrap('project') ?>">
                    <label for="project_id" class="form-label">Link to project *</label>
                    <select id="project_id" name="project_id" class="form-select">
                        <option value="">— Select project —</option>
                        <?php foreach ($projects as $p): ?>
                            <option value="<?= (int) $p['id'] ?>" <?= (string) $curProject === (string) $p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($m = error_for('project_id')): ?><p class="form-error"><?= e($m) ?></p><?php endif; ?>
                </div>

                <div data-cat-wrap="gift" style="<?= $catWrap('gift') ?>">
                    <label for="gift_id" class="form-label">Link to gift *</label>
                    <select id="gift_id" name="gift_id" class="form-select">
                        <option value="">— Select gift —</option>
                        <?php foreach ($gifts as $g): ?>
                            <option value="<?= (int) $g['id'] ?>" <?= (string) $curGift === (string) $g['id'] ? 'selected' : '' ?>><?= e($g['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($m = error_for('gift_id')): ?><p class="form-error"><?= e($m) ?></p><?php endif; ?>
                </div>

                <div data-cat-wrap="event" style="<?= $catWrap('event') ?>">
                    <label for="event_id" class="form-label">Link to event *</label>
                    <select id="event_id" name="event_id" class="form-select">
                        <option value="">— Select event —</option>
                        <?php foreach ($events as $ev): ?>
                            <option value="<?= (int) $ev['id'] ?>" <?= (string) $curEvent === (string) $ev['id'] ? 'selected' : '' ?>><?= e($ev['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($m = error_for('event_id')): ?><p class="form-error"><?= e($m) ?></p><?php endif; ?>
                </div>

                <div>
                    <label for="member_type_filter" class="form-label">Member type (optional)</label>
                    <select id="member_type_filter" name="member_type_filter" class="form-select">
                        <option value="">— All member types —</option>
                        <?php foreach ($memberTypes as $mt): ?>
                            <option value="<?= (int) $mt['id'] ?>" <?= (string) old('member_type_filter') === (string) $mt['id'] ? 'selected' : '' ?>><?= e($mt['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="mt-1 text-xs text-gray-400">Filters the member list on the right — not stored on the due.</p>
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

                <div data-exclude-wrap data-cat-wrap="project" class="mb-3 rounded-lg bg-amber-50 px-3 py-2" style="<?= $catWrap('project') ?>">
                    <label class="flex items-center gap-2 text-sm text-amber-800">
                        <input type="checkbox" data-exclude-existing class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                        Exclude members who already have a due for the selected project
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
                                <tr data-row data-search="<?= e($search) ?>" data-member-type="<?= (int) ($m['member_type_id'] ?? 0) ?>">
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
            <button type="submit" class="btn-primary">Review dues &rarr;</button>
            <a href="<?= e(url('/demands')) ?>" class="btn-secondary">Cancel</a>
        </div>
    </form>
</div>

