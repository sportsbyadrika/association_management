<?php $this->layout('layouts.app'); /** @var list $members */ /** @var array $paginator */
$sortLink = static function (string $col) use ($search, $sort, $dir): string {
    $newDir = ($sort === $col && $dir === 'asc') ? 'desc' : 'asc';
    return url('/members?q=' . urlencode($search) . '&sort=' . $col . '&dir=' . $newDir);
};
?>

<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Members</h1>
        <p class="mt-1 text-sm text-gray-500">Your association's member directory.</p>
    </div>
    <a href="<?= e(url('/members/create')) ?>" class="btn-primary">+ Add Member</a>
</div>

<div class="card">
    <div class="border-b border-gray-100 p-4">
        <form method="get" action="<?= e(url('/members')) ?>" class="flex gap-2">
            <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search name, mobile or email…" class="form-input max-w-sm">
            <button type="submit" class="btn-secondary">Search</button>
            <?php if ($search !== ''): ?><a href="<?= e(url('/members')) ?>" class="btn-secondary">Clear</a><?php endif; ?>
        </form>
    </div>
    <div class="overflow-x-auto">
        <table class="table">
            <thead>
                <tr>
                    <th><a href="<?= e($sortLink('name')) ?>" class="hover:text-gray-700">Name</a></th>
                    <th>Type</th>
                    <th><a href="<?= e($sortLink('age')) ?>" class="hover:text-gray-700">Age</a></th>
                    <th>Gender</th>
                    <th>Mobile</th>
                    <th>Status</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($members as $m): ?>
                <tr>
                    <td>
                        <div class="flex items-center gap-3">
                            <?php if (!empty($m['photo_path'])): ?>
                                <img src="<?= e(url('/photo/member/' . $m['id'])) ?>" alt="" class="h-9 w-9 rounded-full object-cover ring-1 ring-gray-200">
                            <?php else: ?>
                                <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-brand-100 text-sm font-semibold text-brand-700"><?= e(strtoupper(substr($m['name'], 0, 1))) ?></span>
                            <?php endif; ?>
                            <a href="<?= e(url('/members/' . $m['id'])) ?>" class="font-medium text-brand-700 hover:underline"><?= e($m['name']) ?></a>
                        </div>
                    </td>
                    <td><?= e($m['member_type_name'] ?? '—') ?></td>
                    <td><?= e($m['age'] ?? '—') ?></td>
                    <td class="capitalize"><?= e($m['gender'] ?? '—') ?></td>
                    <td><?= e($m['mobile'] ?? '—') ?></td>
                    <td>
                        <?php if ((int) $m['is_active'] === 1): ?>
                            <span class="badge bg-brand-100 text-brand-800">Active</span>
                        <?php else: ?>
                            <span class="badge bg-gray-100 text-gray-600">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-right">
                        <a href="<?= e(url('/members/' . $m['id'] . '/ledger')) ?>" class="text-brand-700 hover:underline">Ledger</a>
                        <span class="text-gray-300">·</span>
                        <a href="<?= e(url('/members/' . $m['id'] . '/edit')) ?>" class="text-brand-700 hover:underline">Edit</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($members === []): ?>
                <tr><td colspan="7" class="text-center text-gray-400 py-8">No members found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="p-4">
        <?php $baseUrl = url('/members?q=' . urlencode($search) . '&sort=' . $sort . '&dir=' . $dir);
        include dirname(__DIR__) . '/partials/pagination.php'; ?>
    </div>
</div>
