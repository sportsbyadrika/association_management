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
