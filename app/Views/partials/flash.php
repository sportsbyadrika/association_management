<?php

use App\Core\Session;

$messages = Session::pullFlash();
if ($messages === []) {
    return;
}
$styles = [
    'success' => 'bg-brand-50 text-brand-800 ring-brand-200',
    'error'   => 'bg-red-50 text-red-800 ring-red-200',
    'warning' => 'bg-amber-50 text-amber-800 ring-amber-200',
    'info'    => 'bg-blue-50 text-blue-800 ring-blue-200',
];
?>
<div class="space-y-2">
    <?php foreach ($messages as $m): ?>
        <?php $cls = $styles[$m['type']] ?? $styles['info']; ?>
        <div class="flex items-start justify-between gap-3 rounded-lg px-4 py-3 text-sm ring-1 <?= $cls ?>" data-flash>
            <span><?= e($m['message']) ?></span>
            <button type="button" data-flash-close class="text-current opacity-60 hover:opacity-100" aria-label="Dismiss">&times;</button>
        </div>
    <?php endforeach; ?>
</div>
