<?php
/**
 * Reusable "form in a modal" control. Include once on any page:
 *   <?php include dirname(__DIR__) . '/partials/form_modal.php'; ?>
 *
 * Then any trigger element opens the modal (event-delegated, so triggers can be
 * anywhere, including rows rendered later):
 *   <button type="button"
 *           data-form-modal="<?= e(url('/receipts/create?category=event&event_id=' . $id . '&embed=1')) ?>"
 *           data-form-modal-title="Add collection">Add collection</button>
 *
 * The URL is loaded in a same-origin iframe using the minimal "embed" layout.
 * On a successful save the embedded page posts a {habitract:'saved'} message and
 * this page reloads so the underlying lists/totals refresh.
 */
?>
<div id="formModal" class="hidden fixed inset-0 z-50 items-center justify-center p-4">
    <div id="formModalBackdrop" class="absolute inset-0 bg-black/40"></div>
    <div class="relative z-10 flex h-[90vh] max-h-[90vh] w-full max-w-3xl flex-col overflow-hidden rounded-lg bg-white shadow-xl">
        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-3">
            <h3 id="formModalTitle" class="font-semibold text-gray-900">Form</h3>
            <button id="formModalClose" type="button" class="text-2xl leading-none text-gray-400 hover:text-gray-700" aria-label="Close">&times;</button>
        </div>
        <iframe id="formModalFrame" title="Form" class="h-full w-full flex-1 border-0"></iframe>
    </div>
</div>
<script>
(function () {
    var modal = document.getElementById('formModal');
    if (!modal || modal.dataset.bound) { return; }
    modal.dataset.bound = '1';
    var frame = document.getElementById('formModalFrame');
    var titleEl = document.getElementById('formModalTitle');

    function open(url, title) {
        titleEl.textContent = title || 'Form';
        frame.src = url;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
    function close() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        frame.src = 'about:blank';
    }

    document.addEventListener('click', function (e) {
        var t = e.target.closest ? e.target.closest('[data-form-modal]') : null;
        if (!t) { return; }
        e.preventDefault();
        open(t.getAttribute('data-form-modal'), t.getAttribute('data-form-modal-title'));
    });
    document.getElementById('formModalClose').addEventListener('click', close);
    document.getElementById('formModalBackdrop').addEventListener('click', close);
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { close(); } });

    // The embedded form signals success -> refresh so lists/totals update.
    window.addEventListener('message', function (e) {
        if (e.data && e.data.habitract === 'saved') { close(); window.location.reload(); }
    });
})();
</script>
