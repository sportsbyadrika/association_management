<?php

use App\Models\Master;

/** Shared tab bar for the Masters section. */
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

$tabs = [];
foreach (Master::LABELS as $key => $label) {
    $tabs[] = [$label, '/masters/' . $key, '/masters/' . $key];
}
$tabs[] = ['Bank Account', '/bank-accounts', '/bank-accounts'];
$tabs[] = ['Financial Year', '/masters/financial-years', '/masters/financial-years'];

$isActive = static function (string $prefix) use ($path): bool {
    return $path === $prefix || str_starts_with($path, $prefix . '/');
};
?>
<div class="mb-6 flex flex-wrap gap-2 border-b border-gray-200">
    <?php foreach ($tabs as [$label, $href, $prefix]): ?>
        <a href="<?= e(url($href)) ?>"
           class="-mb-px border-b-2 px-3 py-2 text-sm font-medium <?= $isActive($prefix) ? 'border-brand-600 text-brand-700' : 'border-transparent text-gray-500 hover:text-gray-700' ?>">
            <?= e($label) ?>
        </a>
    <?php endforeach; ?>
</div>
