<?php /** @var string $appName */ ?>
<!DOCTYPE html>
<html lang="en" class="h-full scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Habitract — membership management software for associations. Manage members, receipts &amp; expenditure, projects and reports in one place.">
    <title>Habitract · Membership Management for Associations</title>
    <link rel="stylesheet" href="<?= e(url('/assets/css/app.css')) ?>">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🏘️</text></svg>">
</head>
<body class="flex min-h-full flex-col bg-white text-gray-900">

<!-- Header -->
<header class="sticky top-0 z-20 border-b border-gray-100 bg-white/90 backdrop-blur">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
        <a href="<?= e(url('/')) ?>" class="flex items-center gap-2">
            <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-brand-700 text-white">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9.5 12 3l9 6.5"/><path d="M5 10v10h14V10"/><path d="M9 20v-6h6v6"/></svg>
            </span>
            <span class="text-xl font-bold text-brand-700">Habitract</span>
        </a>
        <a href="<?= e(url('/login')) ?>" class="btn-primary">Login</a>
    </div>
</header>

<main class="flex-1">
    <!-- Hero -->
    <section class="relative overflow-hidden bg-gradient-to-b from-brand-50 to-white">
        <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8 lg:py-28">
            <div class="mx-auto max-w-3xl text-center">
                <span class="inline-flex items-center rounded-full bg-brand-100 px-3 py-1 text-sm font-medium text-brand-800">Built for residents &amp; community associations</span>
                <h1 class="mt-6 text-4xl font-extrabold tracking-tight text-gray-900 sm:text-5xl lg:text-6xl">
                    Membership management software for <span class="text-brand-700">Associations</span>
                </h1>
                <p class="mx-auto mt-6 max-w-2xl text-lg text-gray-600">
                    Habitract brings your members, subscriptions, receipts, expenditure, projects and reports
                    together in one secure, easy-to-use place — so your association runs smoothly.
                </p>
                <div class="mt-10 flex items-center justify-center gap-4">
                    <a href="<?= e(url('/login')) ?>" class="btn-primary px-6 py-3 text-base">Login to your account</a>
                    <a href="#features" class="btn-secondary px-6 py-3 text-base">Explore features</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section id="features" class="py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-3xl font-bold tracking-tight text-gray-900">Everything your association needs</h2>
                <p class="mt-4 text-gray-600">Purpose-built modules that work together.</p>
            </div>
            <div class="mt-14 grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
                <?php
                $features = [
                    ['Members', 'Maintain a complete member directory with types, contact details, family info and photos.', 'M17 20h5v-2a4 4 0 0 0-3-3.87M9 20H4v-2a4 4 0 0 1 3-3.87m6-1.13a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z'],
                    ['Receipts &amp; Expenditure', 'Record subscriptions, donations and project collections; track every rupee spent.', 'M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6'],
                    ['Projects', 'Run community projects with milestones, photos and collections tracked against targets.', 'M9 11l3 3 8-8M20 12v6a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h9'],
                    ['Reports', 'Generate member, ledger, income and expenditure reports — download as CSV or PDF.', 'M9 17v-6M12 17V7M15 17v-4M4 4h16v16H4z'],
                ];
                foreach ($features as $f): ?>
                    <div class="card card-body">
                        <span class="inline-flex h-11 w-11 items-center justify-center rounded-lg bg-brand-100 text-brand-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="<?= $f[2] ?>"/></svg>
                        </span>
                        <h3 class="mt-4 text-lg font-semibold text-gray-900"><?= $f[0] ?></h3>
                        <p class="mt-2 text-sm text-gray-600"><?= $f[1] ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="bg-brand-700">
        <div class="mx-auto max-w-7xl px-4 py-16 text-center sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-white">Ready to manage your association better?</h2>
            <p class="mx-auto mt-3 max-w-xl text-brand-100">Sign in to get started. Association administrators can add members, record receipts and generate reports in minutes.</p>
            <a href="<?= e(url('/login')) ?>" class="btn mt-8 bg-white px-6 py-3 text-base text-brand-700 hover:bg-brand-50">Login</a>
        </div>
    </section>
</main>

<?php include dirname(__DIR__) . '/partials/footer_public.php'; ?>
</body>
</html>
