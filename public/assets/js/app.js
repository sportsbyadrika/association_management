// Habitract — lightweight vanilla JS interactivity (no framework).
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        // Mobile nav toggle.
        var toggle = document.querySelector('[data-nav-toggle]');
        var menu = document.querySelector('[data-nav-menu]');
        if (toggle && menu) {
            toggle.addEventListener('click', function () {
                menu.classList.toggle('hidden');
            });
        }

        // User dropdown menus (profile, etc.).
        document.querySelectorAll('[data-dropdown-toggle]').forEach(function (btn) {
            var target = document.querySelector(btn.getAttribute('data-dropdown-toggle'));
            if (!target) return;
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                target.classList.toggle('hidden');
            });
        });
        document.addEventListener('click', function () {
            document.querySelectorAll('[data-dropdown]').forEach(function (d) {
                d.classList.add('hidden');
            });
        });

        // Dismiss flash messages.
        document.querySelectorAll('[data-flash-close]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var flash = btn.closest('[data-flash]');
                if (flash) flash.remove();
            });
        });

        // Confirm destructive actions.
        document.querySelectorAll('form[data-confirm]').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                if (!window.confirm(form.getAttribute('data-confirm') || 'Are you sure?')) {
                    e.preventDefault();
                }
            });
        });

        // Member selection tables (bulk raise demand): search filter,
        // select-all (of visible rows) and a live selected counter.
        document.querySelectorAll('[data-member-select]').forEach(function (container) {
            var filter = container.querySelector('[data-member-filter]');
            var selectAll = container.querySelector('[data-select-all]');
            var rows = Array.prototype.slice.call(container.querySelectorAll('[data-row]'));
            var countEl = container.querySelector('[data-selected-count]');
            var emptyEl = container.querySelector('[data-member-empty]');

            function allCbs() { return container.querySelectorAll('[data-member-cb]'); }
            function visibleCbs() {
                return rows.filter(function (r) { return r.style.display !== 'none'; })
                    .map(function (r) { return r.querySelector('[data-member-cb]'); })
                    .filter(Boolean);
            }
            function updateCount() {
                var n = 0;
                allCbs().forEach(function (cb) { if (cb.checked) n++; });
                if (countEl) countEl.textContent = n;
                if (selectAll) {
                    var vis = visibleCbs();
                    selectAll.checked = vis.length > 0 && vis.every(function (cb) { return cb.checked; });
                }
            }
            function applyFilter() {
                var q = (filter.value || '').trim().toLowerCase();
                var anyVisible = false;
                rows.forEach(function (r) {
                    var hay = r.getAttribute('data-search') || '';
                    var show = q === '' || hay.indexOf(q) !== -1;
                    r.style.display = show ? '' : 'none';
                    if (show) anyVisible = true;
                });
                if (emptyEl) emptyEl.classList.toggle('hidden', anyVisible);
                updateCount();
            }
            if (filter) filter.addEventListener('input', applyFilter);
            if (selectAll) {
                selectAll.addEventListener('change', function () {
                    visibleCbs().forEach(function (cb) { cb.checked = selectAll.checked; });
                    updateCount();
                });
            }
            container.addEventListener('change', function (e) {
                if (e.target && e.target.hasAttribute && e.target.hasAttribute('data-member-cb')) updateCount();
            });
            updateCount();
        });

        // Auto-hide success flashes after a few seconds.
        setTimeout(function () {
            document.querySelectorAll('[data-flash]').forEach(function (f) {
                f.style.transition = 'opacity .4s';
                f.style.opacity = '0';
                setTimeout(function () { f.remove(); }, 400);
            });
        }, 6000);
    });
})();
