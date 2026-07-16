<?php

use App\Core\Auth;

/** @var array|null $currentUser */
$user = $currentUser ?? Auth::user();
$role = $user['role'] ?? null;
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

/** Build the role-dependent menu. */
$menu = [];
if ($role === 'super_admin') {
    $menu = [
        ['Associations', '/admin/associations', '/admin/associations'],
        ['Association Admins', '/admin/admins', '/admin/admins'],
    ];
} elseif ($role === 'association_admin') {
    $menu = [
        ['Dashboard', '/dashboard', '/dashboard'],
        ['Members', '/members', '/members'],
        ['Demands', '/demands', '/demands'],
        ['Receipts', '/receipts', '/receipts'],
        ['Expenditure', '/expenditures', '/expenditures'],
        ['Projects', '/projects', '/projects'],
        ['Bank Accounts', '/bank-accounts', '/bank-accounts'],
        ['Masters', '/masters/member-types', '/masters'],
        ['Reports', '/reports', '/reports'],
    ];
} elseif ($role === 'association_staff') {
    $menu = [
        ['Dashboard', '/dashboard', '/dashboard'],
        ['Members', '/members', '/members'],
        ['Demands', '/demands', '/demands'],
        ['Receipts', '/receipts', '/receipts'],
        ['Expenditure', '/expenditures', '/expenditures'],
        ['Projects', '/projects', '/projects'],
        ['Reports', '/reports', '/reports'],
    ];
} elseif ($role === 'member') {
    $menu = [
        ['My Profile', '/member/profile', '/member/profile'],
        ['My Ledger', '/member/ledger', '/member/ledger'],
    ];
}

$isActive = static function (string $prefix) use ($path): bool {
    if ($prefix === '/dashboard' || $prefix === '/') {
        return $path === $prefix;
    }
    return str_starts_with($path, $prefix);
};

// Branding: association logo + name, or Habitract for super admin.
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
                <?php foreach ($menu as [$label, $href, $prefix]): ?>
                    <a href="<?= e(url($href)) ?>"
                       class="nav-link <?= $isActive($prefix) ? 'bg-brand-800 text-white' : 'text-brand-100 hover:bg-brand-600 hover:text-white' ?>">
                        <?= e($label) ?>
                    </a>
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
            <?php foreach ($menu as [$label, $href, $prefix]): ?>
                <a href="<?= e(url($href)) ?>"
                   class="block rounded-md px-3 py-2 text-base font-medium <?= $isActive($prefix) ? 'bg-brand-800 text-white' : 'text-brand-100 hover:bg-brand-600 hover:text-white' ?>">
                    <?= e($label) ?>
                </a>
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
