<?php /** @var list $steps */ /** @var int $active */ ?>
<ol class="mb-6 flex flex-wrap items-center gap-y-2 text-sm">
    <?php foreach ($steps as $i => $label): ?>
        <?php $done = $i < $active; $current = $i === $active; ?>
        <li class="flex items-center">
            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full text-xs font-semibold
                <?= $done ? 'bg-brand-600 text-white' : ($current ? 'bg-brand-100 text-brand-800 ring-2 ring-brand-500' : 'bg-gray-100 text-gray-400') ?>">
                <?= $done ? '✓' : $i + 1 ?>
            </span>
            <span class="ml-2 <?= $current ? 'font-semibold text-gray-900' : 'text-gray-500' ?>"><?= e($label) ?></span>
            <?php if ($i < count($steps) - 1): ?>
                <span class="mx-3 h-px w-8 bg-gray-300"></span>
            <?php endif; ?>
        </li>
    <?php endforeach; ?>
</ol>
