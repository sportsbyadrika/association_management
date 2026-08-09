<?php

use App\Models\Master;

/**
 * Shared tab bar for the Masters section.
 * NOTE: this file is include()d into other views and shares their variable
 * scope, so it must not use common names like $key/$label as loop variables —
 * doing so would clobber the including view's state (e.g. the master $key used
 * to build Edit links).
 */
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

$tabs = [];
foreach (Master::LABELS as $tabKey => $tabLabel) {
    $tabs[] = [$tabLabel, '/masters/' . $tabKey, '/masters/' . $tabKey];
}
$tabs[] = ['Bank Account', '/bank-accounts', '/bank-accounts'];
$tabs[] = ['Financial Year', '/masters/financial-years', '/masters/financial-years'];
$tabs[] = ['Due Purpose', '/masters/demand-purposes', '/masters/demand-purposes'];

$isActive = static function (string $prefix) use ($path): bool {
    return $path === $prefix || str_starts_with($path, $prefix . '/');
};
?>
<div class="mb-6 flex flex-wrap gap-2 border-b border-gray-200">
    <?php foreach ($tabs as $tab): ?>
        <?php [$tabLabel, $tabHref, $tabPrefix] = $tab; ?>
        <a href="<?= e(url($tabHref)) ?>"
           class="-mb-px border-b-2 px-3 py-2 text-sm font-medium <?= $isActive($tabPrefix) ? 'border-brand-600 text-brand-700' : 'border-transparent text-gray-500 hover:text-gray-700' ?>">
            <?= e($tabLabel) ?>
        </a>
    <?php endforeach; ?>
</div>
