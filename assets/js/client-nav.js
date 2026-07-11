/**
 * DynamoMenu — menu client mobile (tiroir latéral)
 */
(function () {
    'use strict';

    function initClientNav() {
        var drawer = document.getElementById('clientNavDrawer');
        var toggle = document.getElementById('clientNavToggle');
        var backdrop = document.getElementById('clientNavBackdrop');

        if (!drawer || !toggle || !backdrop) {
            return;
        }

        function isMobileNav() {
            return window.matchMedia('(max-width: 991.98px)').matches;
        }

        function openDrawer() {
            drawer.classList.add('is-open');
            backdrop.classList.add('is-visible');
            document.body.classList.add('client-nav-open');
            toggle.setAttribute('aria-expanded', 'true');
            drawer.setAttribute('aria-hidden', 'false');
            backdrop.setAttribute('aria-hidden', 'false');
        }

        function closeDrawer() {
            drawer.classList.remove('is-open');
            backdrop.classList.remove('is-visible');
            document.body.classList.remove('client-nav-open');
            toggle.setAttribute('aria-expanded', 'false');
            drawer.setAttribute('aria-hidden', 'true');
            backdrop.setAttribute('aria-hidden', 'true');
        }

        toggle.addEventListener('click', function () {
            if (!isMobileNav()) {
                return;
            }
            if (drawer.classList.contains('is-open')) {
                closeDrawer();
            } else {
                openDrawer();
            }
        });

        backdrop.addEventListener('click', closeDrawer);

        drawer.addEventListener('click', function (e) {
            var link = e.target.closest('a.nav-link, a.client-nav-commander');
            if (!link || !isMobileNav()) {
                return;
            }
            var href = link.getAttribute('href');
            if (!href || href === '#' || href.indexOf('javascript:') === 0) {
                return;
            }
            closeDrawer();
        });

        window.addEventListener('resize', function () {
            if (!isMobileNav()) {
                closeDrawer();
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeDrawer();
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initClientNav);
    } else {
        initClientNav();
    }
})();
