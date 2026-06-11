/**
 * DynamoMenu — thème clair / sombre (login + dashboards)
 */
(function () {
    'use strict';

    var STORAGE_KEY = 'dm_dashboard_theme';

    function applyAppTheme(theme) {
        var root = document.documentElement;
        var body = document.body;

        if (theme === 'light') {
            root.classList.add('theme-light');
            if (body) {
                body.classList.add('theme-light');
            }
        } else {
            root.classList.remove('theme-light');
            if (body) {
                body.classList.remove('theme-light');
            }
            theme = 'dark';
        }

        try {
            localStorage.setItem(STORAGE_KEY, theme);
        } catch (err) {
            /* ignore */
        }

        document.querySelectorAll('[data-theme-set]').forEach(function (btn) {
            var active = btn.getAttribute('data-theme-set') === theme;
            btn.classList.toggle('is-active', active);
            btn.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
    }

    function getStoredTheme() {
        try {
            return localStorage.getItem(STORAGE_KEY) || 'dark';
        } catch (e) {
            return 'dark';
        }
    }

    function initAppTheme() {
        applyAppTheme(getStoredTheme());

        document.querySelectorAll('[data-theme-set]').forEach(function (btn) {
            if (btn.dataset.dmThemeBound === '1') {
                return;
            }
            btn.dataset.dmThemeBound = '1';
            btn.addEventListener('click', function () {
                applyAppTheme(btn.getAttribute('data-theme-set') || 'dark');
            });
        });
    }

    window.DynamoTheme = {
        apply: applyAppTheme,
        init: initAppTheme,
        storageKey: STORAGE_KEY,
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAppTheme);
    } else {
        initAppTheme();
    }
})();
