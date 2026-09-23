<?php $this->layout('layouts.app');
/** @var string $from */ /** @var int $id */ /** @var string $name */ /** @var list $destinations */ /** @var string $backUrl */
use App\Services\ActivityMover;
$icon = ['project' => '🏗️', 'gift' => '🎁', 'event' => '📅'];
$blurb = [
    'project' => 'Has milestones and dues; tracks a target amount.',
    'gift'    => 'A donation received or given, with member contributions.',
    'event'   => 'An event with dates, venue and member contributions.',
];
?>

<div class="mb-6">
    <a href="<?= e(url($backUrl)) ?>" class="text-sm text-gray-500 hover:text-brand-700">&larr; Back</a>
    <h1 class="mt-1 text-2xl font-bold text-gray-900">Move “<?= e($name) ?>”</h1>
    <p class="mt-1 text-sm text-gray-500">Step 1 of 2 · Convert this <?= e(ActivityMover::label($from)) ?> into another type. Its receipts and expenditures come with it.</p>
</div>

<div class="max-w-2xl">
    <div class="grid gap-4 sm:grid-cols-2">
        <?php foreach ($destinations as $to): ?>
            <a href="<?= e(url('/activities/' . $from . '/' . $id . '/move/' . $to)) ?>"
               class="card card-body block transition hover:border-brand-400 hover:shadow-md">
                <div class="flex items-center gap-3">
                    <span class="text-2xl"><?= $icon[$to] ?? '' ?></span>
                    <div>
                        <p class="font-semibold text-gray-900">Move to <?= e(ActivityMover::label($to)) ?></p>
                        <p class="text-xs text-gray-500"><?= e($blurb[$to] ?? '') ?></p>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
    <div class="mt-4">
        <a href="<?= e(url($backUrl)) ?>" class="btn-secondary">Cancel</a>
    </div>
</div>
