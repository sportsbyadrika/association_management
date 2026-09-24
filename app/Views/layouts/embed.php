<?php /** @var array $__sections */ ?>
<!DOCTYPE html>
<html lang="en" class="bg-white">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title><?= e($title ?? 'Form') ?></title>
    <link rel="stylesheet" href="<?= e(asset('/assets/css/app.css')) ?>">
</head>
<body class="bg-white">
    <div class="p-4">
        <div class="mb-3"><?php include dirname(__DIR__) . '/partials/flash.php'; ?></div>
        <?= $__sections['content'] ?? '' ?>
    </div>
    <script src="<?= e(asset('/assets/js/app.js')) ?>"></script>
    <script src="<?= e(asset('/assets/js/cropper.js')) ?>"></script>
    <script>
    // Inside the modal iframe: a cancel/back control closes the modal instead
    // of navigating the iframe to a list page.
    document.addEventListener('click', function (e) {
        var c = e.target.closest ? e.target.closest('[data-embed-cancel]') : null;
        if (!c) { return; }
        e.preventDefault();
        try { parent.postMessage({ habitract: 'cancel' }, '*'); } catch (_) {}
    });
    </script>
</body>
</html>
