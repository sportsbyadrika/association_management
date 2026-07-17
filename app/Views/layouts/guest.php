<?php /** @var array $__sections */ ?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title><?= e(($title ?? 'Welcome') . ' · ' . ($appName ?? 'Habitract')) ?></title>
    <link rel="stylesheet" href="<?= e(url('/assets/css/app.css')) ?>">
    <?php include dirname(__DIR__) . '/partials/head_icons.php'; ?>
</head>
<body class="h-full">
<div class="flex min-h-full flex-col">
    <header class="border-b border-gray-200 bg-white">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
            <a href="<?= e(url('/')) ?>" class="flex items-center gap-2">
                <?= $this->section('brandLogo') ?? '' ?>
                <span class="text-xl font-bold text-brand-700">Habitract</span>
            </a>
            <a href="<?= e(url('/')) ?>" class="text-sm font-medium text-gray-500 hover:text-brand-700">&larr; Back to home</a>
        </div>
    </header>

    <main class="flex flex-1 items-center justify-center px-4 py-12">
        <div class="w-full max-w-md">
            <?php include dirname(__DIR__) . '/partials/flash.php'; ?>
            <div class="mt-4">
                <?= $__sections['content'] ?? '' ?>
            </div>
        </div>
    </main>

    <?php include dirname(__DIR__) . '/partials/footer_public.php'; ?>
</div>
<script src="<?= e(url('/assets/js/app.js')) ?>"></script>
</body>
</html>
