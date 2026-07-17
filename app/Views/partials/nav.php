<?php

use App\Core\Auth;

/** @var array|null $currentUser */
$user = $currentUser ?? Auth::user();
$role = $user['role'] ?? null;
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

// Menu items: either ['type' => 'link', ...] or ['type' => 'dropdown', ...].
$finance = [
    'type'     => 'dropdown',
    'label'    => 'Finance',
    'id'       => 'financeMenu',
    'prefixes' => ['/demands', '/receipts', '/expenditures'],
    'items'    => [
        ['Demands', '/demands'],
        ['Receipts', '/receipts'],
        ['Expenditure', '/expenditures'],
    ],
];

$menu = [];
if ($role === 'super_admin') {
    $menu = [
        ['type' => 'link', 'label' => 'Associations', 'href' => '/admin/associations', 'prefix' => '/admin/associations'],
        ['type' => 'link', 'label' => 'Association Admins', 'href' => '/admin/admins', 'prefix' => '/admin/admins'],
    ];
} elseif ($role === 'association_admin') {
    $menu = [
        ['type' => 'link', 'label' => 'Dashboard', 'href' => '/dashboard', 'prefix' => '/dashboard'],
        ['type' => 'link', 'label' => 'Members', 'href' => '/members', 'prefix' => '/members'],
        $finance,
        ['type' => 'link', 'label' => 'Projects', 'href' => '/projects', 'prefix' => '/projects'],
        ['type' => 'link', 'label' => 'Masters', 'href' => '/masters/member-types', 'prefix' => '/masters', 'extra' => ['/bank-accounts']],
        ['type' => 'link', 'label' => 'Reports', 'href' => '/reports', 'prefix' => '/reports'],
    ];
} elseif ($role === 'association_staff') {
    $menu = [
        ['type' => 'link', 'label' => 'Dashboard', 'href' => '/dashboard', 'prefix' => '/dashboard'],
        ['type' => 'link', 'label' => 'Members', 'href' => '/members', 'prefix' => '/members'],
        $finance,
        ['type' => 'link', 'label' => 'Projects', 'href' => '/projects', 'prefix' => '/projects'],
        ['type' => 'link', 'label' => 'Reports', 'href' => '/reports', 'prefix' => '/reports'],
    ];
} elseif ($role === 'member') {
    $menu = [
        ['type' => 'link', 'label' => 'My Profile', 'href' => '/member/profile', 'prefix' => '/member/profile'],
        ['type' => 'link', 'label' => 'My Ledger', 'href' => '/member/ledger', 'prefix' => '/member/ledger'],
    ];
}

$linkActive = static function (array $item) use ($path): bool {
    $prefix = $item['prefix'] ?? '';
    if ($prefix === '/dashboard') {
        return $path === $prefix;
    }
    $prefixes = array_merge([$prefix], $item['extra'] ?? []);
    foreach ($prefixes as $p) {
        if ($p !== '' && ($path === $p || str_starts_with($path, $p . '/') || str_starts_with($path, $p))) {
            return true;
        }
    }
    return false;
};
$dropActive = static function (array $item) use ($path): bool {
    foreach ($item['prefixes'] ?? [] as $p) {
        if (str_starts_with($path, $p)) {
            return true;
        }
    }
    return false;
};

$brandName = 'Habitract';
$logoPath = null;
if ($role !== 'super_admin' && isset($GLOBALS['__association'])) {
    $brandName = $GLOBALS['__association']['name'] ?? 'Habitract';
    $logoPath = $GLOBALS['__association']['logo_path'] ?? null;
}
?>
<nav class="bg-brand-700 text-white shadow" data-nav>
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between gap-4">
            <!-- Brand -->
            <div class="flex items-center gap-3 min-w-0">
                <?php if ($logoPath): ?>
                    <img src="<?= e(url('/photo/association/' . ($GLOBALS['__association']['id'] ?? 0))) ?>" alt="" class="h-9 w-9 rounded-lg object-cover bg-white">
                <?php else: ?>
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-white/15">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9.5 12 3l9 6.5"/><path d="M5 10v10h14V10"/><path d="M9 20v-6h6v6"/></svg>
                    </span>
                <?php endif; ?>
                <span class="truncate text-lg font-bold"><?= e($brandName) ?></span>
            </div>

            <!-- Desktop menu -->
            <div class="hidden md:flex md:items-center md:gap-1 flex-1 justify-center">
                <?php foreach ($menu as $item): ?>
                    <?php if ($item['type'] === 'link'): ?>
                        <a href="<?= e(url($item['href'])) ?>"
                           class="nav-link <?= $linkActive($item) ? 'bg-brand-800 text-white' : 'text-brand-100 hover:bg-brand-600 hover:text-white' ?>">
                            <?= e($item['label']) ?>
                        </a>
                    <?php else: ?>
                        <div class="relative">
                            <button type="button" data-dropdown-toggle="#<?= e($item['id']) ?>"
                                    class="nav-link <?= $dropActive($item) ? 'bg-brand-800 text-white' : 'text-brand-100 hover:bg-brand-600 hover:text-white' ?>">
                                <?= e($item['label']) ?>
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd"/></svg>
                            </button>
                            <div id="<?= e($item['id']) ?>" data-dropdown class="hidden absolute left-0 z-30 mt-2 w-44 rounded-lg bg-white py-1 text-gray-700 shadow-lg ring-1 ring-black/5">
                                <?php foreach ($item['items'] as [$label, $href]): ?>
                                    <a href="<?= e(url($href)) ?>" class="block px-4 py-2 text-sm hover:bg-gray-50 <?= str_starts_with($path, $href) ? 'font-semibold text-brand-700' : '' ?>"><?= e($label) ?></a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <!-- Right: user menu -->
            <div class="flex items-center gap-2">
                <div class="relative hidden sm:block">
                    <button type="button" data-dropdown-toggle="#userMenu"
                            class="flex items-center gap-2 rounded-lg px-2 py-1.5 hover:bg-brand-600">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-white/20 text-sm font-semibold">
                            <?= e(strtoupper(substr($user['name'] ?? '?', 0, 1))) ?>
                        </span>
                        <span class="hidden text-left leading-tight lg:block">
                            <span class="block text-sm font-medium"><?= e($user['name'] ?? '') ?></span>
                            <span class="block text-xs text-brand-100"><?= e(role_label($role)) ?></span>
                        </span>
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd"/></svg>
                    </button>
                    <div id="userMenu" data-dropdown class="hidden absolute right-0 z-30 mt-2 w-48 rounded-lg bg-white py-1 text-gray-700 shadow-lg ring-1 ring-black/5">
                        <a href="<?= e(url('/profile')) ?>" class="block px-4 py-2 text-sm hover:bg-gray-50">Profile</a>
                        <a href="<?= e(url('/profile/password')) ?>" class="block px-4 py-2 text-sm hover:bg-gray-50">Change password</a>
                        <form method="post" action="<?= e(url('/logout')) ?>">
                            <?= csrf_field() ?>
                            <button type="submit" class="block w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-gray-50">Sign out</button>
                        </form>
                    </div>
                </div>

                <!-- Mobile hamburger -->
                <button type="button" data-nav-toggle class="md:hidden inline-flex items-center justify-center rounded-md p-2 hover:bg-brand-600" aria-label="Toggle menu">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile menu -->
    <div class="hidden md:hidden border-t border-brand-600" data-nav-menu>
        <div class="space-y-1 px-2 pb-3 pt-2">
            <?php foreach ($menu as $item): ?>
                <?php if ($item['type'] === 'link'): ?>
                    <a href="<?= e(url($item['href'])) ?>"
                       class="block rounded-md px-3 py-2 text-base font-medium <?= $linkActive($item) ? 'bg-brand-800 text-white' : 'text-brand-100 hover:bg-brand-600 hover:text-white' ?>">
                        <?= e($item['label']) ?>
                    </a>
                <?php else: ?>
                    <p class="px-3 pt-2 text-xs font-semibold uppercase tracking-wide text-brand-200"><?= e($item['label']) ?></p>
                    <?php foreach ($item['items'] as [$label, $href]): ?>
                        <a href="<?= e(url($href)) ?>"
                           class="block rounded-md px-3 py-2 pl-6 text-base font-medium <?= str_starts_with($path, $href) ? 'bg-brand-800 text-white' : 'text-brand-100 hover:bg-brand-600 hover:text-white' ?>">
                            <?= e($label) ?>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endforeach; ?>
            <div class="mt-3 border-t border-brand-600 pt-3">
                <p class="px-3 text-sm font-medium"><?= e($user['name'] ?? '') ?> · <span class="text-brand-100"><?= e(role_label($role)) ?></span></p>
                <a href="<?= e(url('/profile')) ?>" class="block rounded-md px-3 py-2 text-base font-medium text-brand-100 hover:bg-brand-600">Profile</a>
                <a href="<?= e(url('/profile/password')) ?>" class="block rounded-md px-3 py-2 text-base font-medium text-brand-100 hover:bg-brand-600">Change password</a>
                <form method="post" action="<?= e(url('/logout')) ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="block w-full rounded-md px-3 py-2 text-left text-base font-medium text-red-200 hover:bg-brand-600">Sign out</button>
                </form>
            </div>
        </div>
    </div>
</nav>
