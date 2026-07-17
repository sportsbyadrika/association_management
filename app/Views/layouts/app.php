<?php

use App\Core\Auth;
use App\Models\Association;

/** @var array $__sections */

// Load the current association for branding (non super admins).
if (!isset($GLOBALS['__association'])) {
    $GLOBALS['__association'] = null;
    $assocId = Auth::associationId();
    if ($assocId !== null) {
        $GLOBALS['__association'] = (new Association())->find($assocId);
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title><?= e(($title ?? 'Dashboard') . ' · ' . ($appName ?? 'Habitract')) ?></title>
    <link rel="stylesheet" href="<?= e(asset('/assets/css/app.css')) ?>">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🏘️</text></svg>">
</head>
<body class="flex min-h-full flex-col">
    <?php include dirname(__DIR__) . '/partials/nav.php'; ?>

    <main class="flex-1">
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <?php if (!empty($pageHeader ?? null) || !empty($pageActions ?? null)): ?>
                <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900"><?= e($pageHeader ?? ($title ?? '')) ?></h1>
                        <?php if (!empty($pageSubtitle ?? null)): ?>
                            <p class="mt-1 text-sm text-gray-500"><?= e($pageSubtitle) ?></p>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($pageActions ?? null)): ?>
                        <div class="flex items-center gap-2"><?= $pageActions ?></div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="mb-4"><?php include dirname(__DIR__) . '/partials/flash.php'; ?></div>

            <?= $__sections['content'] ?? '' ?>
        </div>
    </main>

    <footer class="border-t border-gray-200 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
            <p class="text-center text-xs text-gray-400">
                &copy; <?= date('Y') ?>
                <a href="https://sportsbya.com" target="_blank" rel="noopener noreferrer" class="hover:text-brand-700">SportsByA Tech (OPC) Private Limited</a>
                · Habitract
            </p>
        </div>
    </footer>

    <script src="<?= e(asset('/assets/js/app.js')) ?>"></script>
    <script src="<?= e(asset('/assets/js/cropper.js')) ?>"></script>
</body>
</html>
