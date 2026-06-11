/**
 * Injection automatique du token CSRF dans les formulaires POST.
 */
(function () {
    'use strict';

    function getToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function ensureFormToken(form) {
        var method = (form.getAttribute('method') || 'get').toLowerCase();
        if (method !== 'post') {
            return;
        }
        if (form.querySelector('input[name="_csrf"]')) {
            return;
        }
        var token = getToken();
        if (!token) {
            return;
        }
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = '_csrf';
        input.value = token;
        form.prepend(input);
    }

    function init() {
        document.querySelectorAll('form').forEach(ensureFormToken);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
