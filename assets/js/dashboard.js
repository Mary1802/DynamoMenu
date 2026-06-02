/**
 * DynamoMenu — navigation latérale des dashboards (mobile / tablette)
 */
(function () {
    'use strict';

    function initDashboardNav() {
        var sidebar = document.getElementById('dashboardSidebar');
        var toggle = document.getElementById('sidebarToggle');
        var backdrop = document.getElementById('sidebarBackdrop');

        if (!sidebar || !toggle || !backdrop) {
            return;
        }

        function openSidebar() {
            sidebar.classList.add('is-open');
            backdrop.classList.add('is-visible');
            document.body.classList.add('sidebar-open');
            toggle.setAttribute('aria-expanded', 'true');
        }

        function closeSidebar() {
            sidebar.classList.remove('is-open');
            backdrop.classList.remove('is-visible');
            document.body.classList.remove('sidebar-open');
            toggle.setAttribute('aria-expanded', 'false');
        }

        toggle.addEventListener('click', function () {
            if (sidebar.classList.contains('is-open')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });

        backdrop.addEventListener('click', closeSidebar);

        sidebar.querySelectorAll('.nav-link').forEach(function (link) {
            link.addEventListener('click', function () {
                if (window.matchMedia('(max-width: 991.98px)').matches) {
                    closeSidebar();
                }
            });
        });

        window.addEventListener('resize', function () {
            if (window.matchMedia('(min-width: 992px)').matches) {
                closeSidebar();
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeSidebar();
            }
        });
    }

    function initDashboardSearch() {
        var input = document.querySelector('[data-dashboard-search]');
        if (!input) {
            return;
        }
        input.addEventListener('input', function () {
            var q = (input.value || '').toLowerCase().trim();
            document.querySelectorAll('[data-searchable]').forEach(function (el) {
                var blob = (el.getAttribute('data-search') || '').toLowerCase();
                var show = !q || blob.indexOf(q) !== -1;
                el.classList.toggle('is-search-hidden', !show);
            });
        });
    }

    function initNotificationPanel() {
        var toggle = document.getElementById('notifToggle');
        var panel = document.getElementById('notifPanel');
        if (!toggle || !panel) {
            return;
        }

        function closePanel() {
            panel.hidden = true;
            toggle.setAttribute('aria-expanded', 'false');
        }

        toggle.addEventListener('click', function (e) {
            e.stopPropagation();
            var open = panel.hidden;
            panel.hidden = !open;
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });

        document.addEventListener('click', function (e) {
            if (!panel.hidden && !panel.contains(e.target) && e.target !== toggle && !toggle.contains(e.target)) {
                closePanel();
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closePanel();
            }
        });
    }

    function initDashboard() {
        initDashboardNav();
        initDashboardSearch();
        initNotificationPanel();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDashboard);
    } else {
        initDashboard();
    }
})();
