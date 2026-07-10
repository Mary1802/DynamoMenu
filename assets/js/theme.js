/**
 * DynamoMenu — thème clair / sombre (login + dashboards)
 */
(function () {
    'use strict';

    var STORAGE_KEY = 'dm_dashboard_theme';

    function syncThemeToggle(theme) {
        var isLight = theme === 'light';
        document.querySelectorAll('[data-theme-toggle]').forEach(function (input) {
            input.checked = isLight;
            input.setAttribute('aria-checked', isLight ? 'true' : 'false');
        });
    }

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

        syncThemeToggle(theme);
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

        document.querySelectorAll('[data-theme-toggle]').forEach(function (input) {
            if (input.dataset.dmThemeBound === '1') {
                return;
            }
            input.dataset.dmThemeBound = '1';
            input.addEventListener('change', function () {
                applyAppTheme(input.checked ? 'light' : 'dark');
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
