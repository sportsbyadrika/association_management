<?php
/** @var array $paginator  keys: page, pages, total */
/** @var string $baseUrl  base path with existing query (without page) */
if (($paginator['pages'] ?? 1) <= 1) {
    return;
}
$page = (int) $paginator['page'];
$pages = (int) $paginator['pages'];
$sep = str_contains($baseUrl, '?') ? '&' : '?';
$link = static fn (int $p): string => e($baseUrl . $sep . 'page=' . $p);
$start = max(1, $page - 2);
$end = min($pages, $page + 2);
?>
<nav class="mt-4 flex items-center justify-between" aria-label="Pagination">
    <p class="text-sm text-gray-500">
        Page <?= $page ?> of <?= $pages ?> · <?= (int) $paginator['total'] ?> record(s)
    </p>
    <div class="flex items-center gap-1">
        <?php if ($page > 1): ?>
            <a href="<?= $link($page - 1) ?>" class="btn-secondary btn-sm">Prev</a>
        <?php endif; ?>
        <?php for ($i = $start; $i <= $end; $i++): ?>
            <a href="<?= $link($i) ?>"
               class="btn-sm rounded-lg px-3 py-1.5 text-xs font-semibold <?= $i === $page ? 'bg-brand-700 text-white' : 'bg-white text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-50' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>
        <?php if ($page < $pages): ?>
            <a href="<?= $link($page + 1) ?>" class="btn-secondary btn-sm">Next</a>
        <?php endif; ?>
    </div>
</nav>
