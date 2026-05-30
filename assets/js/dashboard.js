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

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDashboardNav);
    } else {
        initDashboardNav();
    }
})();
