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

            // Optional "exclude members who already have a demand for this
            // project" controls (present only on the Raise Demand page).
            var purposeSel = document.getElementById('purpose');
            var projectSel = document.getElementById('project_id');
            var excludeToggle = container.querySelector('[data-exclude-existing]');
            var excludeWrap = document.querySelector('[data-exclude-wrap]');
            var excludedCountEl = container.querySelector('[data-excluded-count]');
            var existingMap = {};
            try { existingMap = JSON.parse(container.getAttribute('data-existing-demands') || '{}'); } catch (e) { existingMap = {}; }

            function excludedSet() {
                if (!excludeToggle || !excludeToggle.checked || !purposeSel || purposeSel.value !== 'project') return null;
                if (!projectSel || !projectSel.value) return null;
                var list = existingMap[projectSel.value] || [];
                var set = {};
                list.forEach(function (id) { set[String(id)] = true; });
                return set;
            }

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
                var q = (filter && filter.value || '').trim().toLowerCase();
                var excluded = excludedSet();
                var anyVisible = false;
                var excludedShown = 0;
                rows.forEach(function (r) {
                    var cb = r.querySelector('[data-member-cb]');
                    var hay = r.getAttribute('data-search') || '';
                    var passesSearch = q === '' || hay.indexOf(q) !== -1;
                    var isExcluded = excluded && cb && excluded[cb.value] === true;
                    var show = passesSearch && !isExcluded;
                    r.style.display = show ? '' : 'none';
                    if (isExcluded && cb && cb.checked) cb.checked = false; // never submit excluded
                    if (show) anyVisible = true;
                    if (passesSearch && isExcluded) excludedShown++;
                });
                if (emptyEl) emptyEl.classList.toggle('hidden', anyVisible);
                if (excludedCountEl) excludedCountEl.textContent = excluded ? '(' + excludedShown + ' hidden)' : '';
                updateCount();
            }
            if (filter) filter.addEventListener('input', applyFilter);
            if (selectAll) {
                selectAll.addEventListener('change', function () {
                    visibleCbs().forEach(function (cb) { cb.checked = selectAll.checked; });
                    updateCount();
                });
            }
            if (excludeToggle) excludeToggle.addEventListener('change', applyFilter);
            if (projectSel) projectSel.addEventListener('change', applyFilter);
            if (purposeSel) {
                purposeSel.addEventListener('change', function () {
                    if (excludeWrap) excludeWrap.style.display = purposeSel.value === 'project' ? 'block' : 'none';
                    applyFilter();
                });
            }
            container.addEventListener('change', function (e) {
                if (e.target && e.target.hasAttribute && e.target.hasAttribute('data-member-cb')) updateCount();
            });
            applyFilter();
        });

        // Live total for editable per-member amounts (demand confirm page).
        document.querySelectorAll('[data-amount-sum]').forEach(function (scope) {
            var totals = scope.querySelectorAll('[data-amount-total]');
            function recompute() {
                var sum = 0;
                scope.querySelectorAll('[data-amount-input]').forEach(function (inp) {
                    var v = parseFloat(inp.value);
                    if (!isNaN(v) && v > 0) sum += v;
                });
                var text = sum.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                totals.forEach(function (t) { t.textContent = text; });
            }
            scope.addEventListener('input', function (e) {
                if (e.target && e.target.hasAttribute && e.target.hasAttribute('data-amount-input')) recompute();
            });
            recompute();
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
