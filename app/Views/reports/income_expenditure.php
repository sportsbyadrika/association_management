<?php $this->layout('layouts.app');
/** @var array $consolidated */ /** @var array $detailed */ /** @var string $tab */
/** @var ?string $from */ /** @var ?string $to */
$qs = 'from=' . urlencode((string) $from) . '&to=' . urlencode((string) $to);
$active = $tab === 'detailed' ? $detailed : $consolidated;

/**
 * Render one statement as a side-by-side Income | Expenditure table.
 * @param array $s
 */
$renderStatement = static function (array $s): void {
    $income = $s['income'];
    $expense = $s['expense'];
    $n = max(count($income), count($expense));
    ?>
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                    <tr class="bg-brand-50">
                        <th colspan="3" class="text-center text-brand-800">Income</th>
                        <th colspan="3" class="text-center text-brand-800">Expenditure</th>
                    </tr>
                    <tr>
                        <th>Date</th><th>Particulars</th><th class="text-right">Income</th>
                        <th>Date</th><th>Particulars</th><th class="text-right">Expense</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($n === 0): ?>
                    <tr><td colspan="6" class="text-center text-gray-400 py-8">No income or expenditure recorded.</td></tr>
                <?php endif; ?>
                <?php for ($i = 0; $i < $n; $i++): $inc = $income[$i] ?? null; $exp = $expense[$i] ?? null; ?>
                    <tr>
                        <td class="text-gray-500"><?= $inc ? e($inc['date']) : '' ?></td>
                        <td class="text-gray-700">
                            <?php if ($inc): $d = $inc['drill'] ?? []; ?>
                                <button type="button" class="ie-drill text-left text-brand-700 hover:underline"
                                    data-side="<?= e($d['side'] ?? 'income') ?>"
                                    <?= isset($d['head']) ? 'data-head="' . e($d['head']) . '"' : '' ?>
                                    <?= isset($d['activity']) ? 'data-activity="' . e($d['activity']) . '"' : '' ?>
                                    data-label="<?= e($inc['particulars']) ?>"><?= e($inc['particulars']) ?></button>
                            <?php endif; ?>
                        </td>
                        <td class="text-right text-brand-700"><?= $inc ? '₹ ' . money($inc['amount']) : '' ?></td>
                        <td class="text-gray-500 border-l border-gray-200"><?= $exp ? e($exp['date']) : '' ?></td>
                        <td class="text-gray-700">
                            <?php if ($exp): $d = $exp['drill'] ?? []; ?>
                                <button type="button" class="ie-drill text-left text-red-700 hover:underline"
                                    data-side="<?= e($d['side'] ?? 'expense') ?>"
                                    <?= isset($d['head']) ? 'data-head="' . e($d['head']) . '"' : '' ?>
                                    <?= isset($d['activity']) ? 'data-activity="' . e($d['activity']) . '"' : '' ?>
                                    data-label="<?= e($exp['particulars']) ?>"><?= e($exp['particulars']) ?></button>
                            <?php endif; ?>
                        </td>
                        <td class="text-right text-red-600"><?= $exp ? '₹ ' . money($exp['amount']) : '' ?></td>
                    </tr>
                <?php endfor; ?>
                </tbody>
                <tfoot>
                    <tr class="bg-gray-50 font-semibold">
                        <td></td><td class="text-right">Total</td><td class="text-right text-brand-700">₹ <?= money($s['incomeTotal']) ?></td>
                        <td class="border-l border-gray-200"></td><td class="text-right">Total</td><td class="text-right text-red-600">₹ <?= money($s['expenseTotal']) ?></td>
                    </tr>
                    <tr class="font-semibold">
                        <td></td><td></td><td></td>
                        <td class="border-l border-gray-200"></td>
                        <td class="text-right">Balance</td>
                        <td class="text-right <?= $s['balance'] < 0 ? 'text-red-600' : 'text-gray-900' ?>">₹ <?= money($s['balance']) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    <?php
};
?>

<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <a href="<?= e(url('/reports')) ?>" class="text-sm text-gray-500 hover:text-brand-700">&larr; Reports</a>
        <h1 class="mt-1 text-2xl font-bold text-gray-900">Income &amp; Expenditure Report</h1>
        <p class="mt-1 text-sm text-gray-500">Income (received, by income head) versus expenditure (by activity), with closing balance.</p>
    </div>
    <div class="flex gap-2">
        <a href="<?= e(url('/reports/income-expenditure?' . $qs . '&tab=' . $tab . '&format=csv')) ?>" class="btn-secondary btn-sm">CSV</a>
        <a href="<?= e(url('/reports/income-expenditure?' . $qs . '&tab=' . $tab . '&format=pdf')) ?>" target="_blank" rel="noopener" class="btn-primary btn-sm">PDF</a>
    </div>
</div>

<form method="get" action="<?= e(url('/reports/income-expenditure')) ?>" class="card card-body mb-6 flex flex-wrap items-end gap-3">
    <input type="hidden" name="tab" value="<?= e($tab) ?>">
    <div>
        <label for="from" class="form-label">From</label>
        <input type="date" id="from" name="from" value="<?= e($from ?? '') ?>" class="form-input">
    </div>
    <div>
        <label for="to" class="form-label">To</label>
        <input type="date" id="to" name="to" value="<?= e($to ?? '') ?>" class="form-input">
    </div>
    <button type="submit" class="btn-primary">Apply</button>
    <?php if ($from || $to): ?><a href="<?= e(url('/reports/income-expenditure?tab=' . $tab)) ?>" class="btn-secondary">Reset</a><?php endif; ?>
</form>

<!-- Tabs -->
<div class="mb-4 flex gap-1 border-b border-gray-200">
    <?php foreach (['consolidated' => 'Consolidated', 'detailed' => 'Detailed'] as $key => $label): ?>
        <a href="<?= e(url('/reports/income-expenditure?' . $qs . '&tab=' . $key)) ?>"
           class="-mb-px border-b-2 px-4 py-2 text-sm font-medium <?= $tab === $key ? 'border-brand-600 text-brand-700' : 'border-transparent text-gray-500 hover:text-gray-700' ?>">
            <?= e($label) ?>
        </a>
    <?php endforeach; ?>
</div>

<div class="mb-4 grid gap-4 sm:grid-cols-3">
    <div class="card card-body"><p class="text-sm text-gray-500">Total income</p><p class="mt-1 text-xl font-bold text-brand-700">₹ <?= money($active['incomeTotal']) ?></p></div>
    <div class="card card-body"><p class="text-sm text-gray-500">Total expense</p><p class="mt-1 text-xl font-bold text-red-600">₹ <?= money($active['expenseTotal']) ?></p></div>
    <div class="card card-body"><p class="text-sm text-gray-500">Balance</p><p class="mt-1 text-xl font-bold <?= $active['balance'] < 0 ? 'text-red-600' : 'text-gray-900' ?>">₹ <?= money($active['balance']) ?></p></div>
</div>

<p class="mt-3 text-xs text-gray-400">Tip: click any particulars entry to see the individual receipts / expenditures behind it.</p>

<?php $renderStatement($active); ?>

<!-- Drill-down modal -->
<div id="ieModal" class="hidden fixed inset-0 z-50 items-center justify-center p-4">
    <div id="ieModalBackdrop" class="absolute inset-0 bg-black/40"></div>
    <div class="relative z-10 flex max-h-[85vh] w-full max-w-4xl flex-col overflow-hidden rounded-lg bg-white shadow-xl">
        <div class="flex items-start justify-between border-b border-gray-100 px-5 py-3">
            <div>
                <h3 id="ieModalTitle" class="font-semibold text-gray-900">Details</h3>
                <p id="ieModalMeta" class="text-xs text-gray-500"></p>
            </div>
            <button id="ieModalClose" type="button" class="ml-4 text-2xl leading-none text-gray-400 hover:text-gray-700">&times;</button>
        </div>
        <div class="overflow-auto">
            <table class="table">
                <thead><tr>
                    <th>Date</th><th>Head</th><th>For</th><th>From</th><th>Mode</th><th>Remarks</th><th class="text-right">Amount</th>
                </tr></thead>
                <tbody id="ieModalBody"></tbody>
            </table>
        </div>
    </div>
</div>

<script>
(function () {
    var base = <?= json_encode(url('/reports/income-expenditure/items')) ?>;
    var qs = <?= json_encode('from=' . urlencode((string) $from) . '&to=' . urlencode((string) $to)) ?>;
    var modal = document.getElementById('ieModal');
    var titleEl = document.getElementById('ieModalTitle');
    var metaEl = document.getElementById('ieModalMeta');
    var bodyEl = document.getElementById('ieModalBody');
    if (!modal) { return; }

    function esc(s) {
        s = (s == null ? '' : String(s));
        return s.replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }
    function open() { modal.classList.remove('hidden'); modal.classList.add('flex'); }
    function close() { modal.classList.add('hidden'); modal.classList.remove('flex'); }

    document.querySelectorAll('.ie-drill').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var params = new URLSearchParams(qs);
            params.set('side', btn.getAttribute('data-side') || 'income');
            if (btn.hasAttribute('data-head')) { params.set('head', btn.getAttribute('data-head')); }
            if (btn.hasAttribute('data-activity')) { params.set('activity', btn.getAttribute('data-activity')); }
            titleEl.textContent = btn.getAttribute('data-label') || 'Details';
            metaEl.textContent = '';
            bodyEl.innerHTML = '<tr><td colspan="7" class="text-center text-gray-400 py-6">Loading…</td></tr>';
            open();
            fetch(base + '?' + params.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    var rows = data.items || [];
                    if (!rows.length) {
                        bodyEl.innerHTML = '<tr><td colspan="7" class="text-center text-gray-400 py-6">No items found.</td></tr>';
                        return;
                    }
                    var amtClass = data.side === 'expense' ? 'text-red-600' : 'text-brand-700';
                    bodyEl.innerHTML = rows.map(function (it) {
                        return '<tr>'
                            + '<td>' + esc(it.date) + '</td>'
                            + '<td>' + esc(it.head) + '</td>'
                            + '<td>' + esc(it.activity) + '</td>'
                            + '<td>' + esc(it.party) + '</td>'
                            + '<td class="capitalize">' + esc(it.mode) + '</td>'
                            + '<td class="max-w-xs truncate" title="' + esc(it.remarks) + '">' + esc(it.remarks || '—') + '</td>'
                            + '<td class="text-right ' + amtClass + '">₹ ' + esc(it.amount) + '</td>'
                            + '</tr>';
                    }).join('');
                    metaEl.textContent = data.count + ' item(s) · Total ₹ ' + data.total;
                })
                .catch(function () {
                    bodyEl.innerHTML = '<tr><td colspan="7" class="text-center text-red-500 py-6">Could not load items.</td></tr>';
                });
        });
    });

    document.getElementById('ieModalClose').addEventListener('click', close);
    document.getElementById('ieModalBackdrop').addEventListener('click', close);
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { close(); } });
})();
</script>
