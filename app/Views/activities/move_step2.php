<?php $this->layout('layouts.app');
/** @var string $from */ /** @var string $to */ /** @var int $id */ /** @var string $name */
/** @var array $impact */ /** @var list $types */ /** @var string $backUrl */
use App\Services\ActivityMover;
$fromL = ActivityMover::label($from);
$toL = ActivityMover::label($to);
?>

<div class="mb-6">
    <a href="<?= e(url($backUrl)) ?>" class="text-sm text-gray-500 hover:text-brand-700">&larr; Choose a different type</a>
    <h1 class="mt-1 text-2xl font-bold text-gray-900">Move “<?= e($name) ?>” to <?= e($toL) ?></h1>
    <p class="mt-1 text-sm text-gray-500">Step 2 of 2 · Review what carries over, then confirm.</p>
</div>

<div class="max-w-2xl space-y-6">
    <!-- What carries over -->
    <div class="card card-body">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">What moves</h2>
        <ul class="mt-3 space-y-2 text-sm text-gray-700">
            <li class="flex items-center gap-2">
                <span class="text-brand-600">✓</span>
                <span><strong><?= (int) $impact['receipts_count'] ?></strong> receipt(s) — ₹ <?= money($impact['receipts_sum']) ?> — re-linked to the new <?= e($toL) ?>.</span>
            </li>
            <li class="flex items-center gap-2">
                <span class="text-brand-600">✓</span>
                <span><strong><?= (int) $impact['exp_count'] ?></strong> expenditure(s) — ₹ <?= money($impact['exp_sum']) ?> — re-linked to the new <?= e($toL) ?>.</span>
            </li>
            <?php if (in_array($from, ['gift', 'event'], true) && in_array($to, ['gift', 'event'], true) && (int) $impact['contrib_count'] > 0): ?>
                <li class="flex items-center gap-2">
                    <span class="text-brand-600">✓</span>
                    <span><strong><?= (int) $impact['contrib_count'] ?></strong> member contribution(s) carried over.</span>
                </li>
            <?php endif; ?>
        </ul>

        <?php
        $warnings = [];
        if ($from === 'project' && (int) $impact['milestones'] > 0) {
            $warnings[] = (int) $impact['milestones'] . ' milestone(s) and their photos will be permanently deleted (' . $toL . 's have no milestones).';
        }
        if ($from === 'project' && (int) $impact['dues'] > 0) {
            $warnings[] = (int) $impact['dues'] . ' due(s) will be removed. Receipts already collected are kept and re-linked; the outstanding dues tracking is dropped.';
        }
        if ($from === 'gift' && $to === 'project' && (int) $impact['contrib_count'] > 0) {
            $warnings[] = (int) $impact['contrib_count'] . ' gift member contribution(s) will be dropped (projects track member money via dues, not contributions).';
        }
        if ($from === 'event' && $to === 'project' && (int) $impact['contrib_count'] > 0) {
            $warnings[] = (int) $impact['contrib_count'] . ' event member contribution(s) will be dropped (projects track member money via dues, not contributions).';
        }
        ?>
        <?php if ($warnings !== []): ?>
            <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-4">
                <p class="text-sm font-semibold text-amber-800">This cannot be undone:</p>
                <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-amber-800">
                    <?php foreach ($warnings as $w): ?><li><?= e($w) ?></li><?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <p class="mt-3 text-xs text-gray-400">The type resets (each type uses its own master), and any fields the new type doesn't have are cleared.</p>
    </div>

    <!-- Destination-only fields + confirm -->
    <form method="post" action="<?= e(url('/activities/' . $from . '/' . $id . '/move/' . $to)) ?>" class="card card-body space-y-5"
          data-confirm="Move “<?= e($name) ?>” to <?= e($toL) ?>? This cannot be undone.">
        <?= csrf_field() ?>
        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="type_id" class="form-label"><?= e($toL) ?> type</label>
                <select id="type_id" name="type_id" class="form-select">
                    <option value="">— Select —</option>
                    <?php foreach ($types as $t): ?>
                        <option value="<?= (int) $t['id'] ?>"><?= e($t['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($to === 'gift'): ?>
                <div>
                    <label for="direction" class="form-label">Direction *</label>
                    <select id="direction" name="direction" class="form-select">
                        <option value="in">Received (donation in)</option>
                        <option value="out">Given (gift out)</option>
                    </select>
                </div>
            <?php endif; ?>
        </div>
        <div class="flex gap-2 border-t border-gray-100 pt-4">
            <button type="submit" class="btn-primary">Confirm move to <?= e($toL) ?></button>
            <a href="<?= e(url($backUrl)) ?>" class="btn-secondary">Back</a>
        </div>
    </form>
</div>
