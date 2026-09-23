<?php $this->layout('layouts.app');
/** @var array $demand */ /** @var array|null $member */ /** @var string $category */
/** @var list $projects */ /** @var list $gifts */ /** @var list $events */
$member = $member ?? null;
$curCat = old('category', $category);
$curProject = old('project_id', (string) ($demand['project_id'] ?? ''));
$curGift = old('gift_id', (string) ($demand['gift_id'] ?? ''));
$curEvent = old('event_id', (string) ($demand['event_id'] ?? ''));
$catWrap = static fn (string $c) => 'display:' . ($curCat === $c ? 'block' : 'none');
$memberLabel = $member ? ($member['name'] . ($member['member_number'] ? ' (' . $member['member_number'] . ')' : '')) : ('Member #' . (int) $demand['member_id']);
?>

<div class="mb-6">
    <a href="<?= e(url('/demands')) ?>" class="text-sm text-gray-500 hover:text-brand-700">&larr; Back to dues</a>
    <h1 class="mt-1 text-2xl font-bold text-gray-900">Edit Due</h1>
    <p class="mt-1 text-sm text-gray-500">Update this due's details. The member cannot be changed.</p>
</div>

<div class="max-w-2xl card card-body">
    <div class="mb-5 rounded-lg bg-gray-50 px-4 py-3 text-sm text-gray-700 ring-1 ring-gray-200">
        Member: <span class="font-semibold text-gray-900"><?= e($memberLabel) ?></span>
        <span class="ml-2 badge capitalize <?= $demand['status'] === 'paid' ? 'bg-brand-100 text-brand-800' : ($demand['status'] === 'partial' ? 'bg-blue-100 text-blue-800' : 'bg-amber-100 text-amber-800') ?>"><?= e($demand['status']) ?></span>
    </div>

    <form method="post" action="<?= e(url('/demands/' . $demand['id'])) ?>" novalidate class="space-y-5">
        <?= csrf_field() ?>

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
            <label for="amount" class="form-label">Amount (₹) *</label>
            <input type="number" step="0.01" min="0.01" id="amount" name="amount" value="<?= old('amount', (string) $demand['amount']) ?>" required class="form-input">
            <?php if ($m = error_for('amount')): ?><p class="form-error"><?= e($m) ?></p><?php endif; ?>
        </div>

        <div>
            <label for="due_date" class="form-label">Due date</label>
            <input type="date" id="due_date" name="due_date" value="<?= old('due_date', (string) ($demand['due_date'] ?? '')) ?>" class="form-input">
        </div>

        <div>
            <label for="remarks" class="form-label">Remarks</label>
            <textarea id="remarks" name="remarks" rows="2" class="form-input"><?= old('remarks', (string) ($demand['remarks'] ?? '')) ?></textarea>
            <?php if ($m = error_for('remarks')): ?><p class="form-error"><?= e($m) ?></p><?php endif; ?>
        </div>

        <div class="flex gap-2">
            <button type="submit" class="btn-primary">Save changes</button>
            <a href="<?= e(url('/demands')) ?>" class="btn-secondary">Cancel</a>
        </div>
    </form>
</div>
