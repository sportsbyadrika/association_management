<?php /** @var int $code */ /** @var string $message */ ?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($code) ?> · Habitract</title>
    <link rel="stylesheet" href="<?= e(url('/assets/css/app.css')) ?>">
</head>
<body class="flex min-h-full items-center justify-center px-4">
    <div class="text-center">
        <p class="text-6xl font-extrabold text-brand-700"><?= e($code) ?></p>
        <h1 class="mt-4 text-2xl font-bold text-gray-900">
            <?= match ((int) $code) {
                403 => 'Access denied',
                404 => 'Page not found',
                500 => 'Something went wrong',
                default => 'Error',
            } ?>
        </h1>
        <p class="mt-2 text-gray-600"><?= e($message) ?></p>
        <a href="<?= e(url('/')) ?>" class="btn-primary mt-8">Go home</a>
    </div>
</body>
</html>
