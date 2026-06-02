<?php

require_once __DIR__ . '/../includes/staff_auth.php';
staff_require(['cuisinier']);

require_once __DIR__ . '/../includes/dashboard_helpers.php';
$user = staff_user();
$contacts = dashboard_contacts();
?>
<!doctype html>
<html lang="fr">
<head>
    <?php dashboard_asset_links('Cuisine — Paramètres'); ?>
</head>
<body class="dashboard-body">
    <div class="sidebar-backdrop" id="sidebarBackdrop" aria-hidden="true"></div>
    <header class="dashboard-topbar">
        <button type="button" class="dashboard-menu-toggle" id="sidebarToggle" aria-label="Menu"><i class="bi bi-list"></i></button>
        <div class="dashboard-topbar-brand">Dynamo<span>Menu</span></div>
        <div style="width:42px;"></div>
    </header>
    <div class="dashboard-shell">
        <aside class="dashboard-sidebar d-flex flex-column" id="dashboardSidebar">
            <div class="sidebar-brand">
                <div class="brand-logo">DM</div>
                <div class="brand-title">DynamoMenu</div>
                <div class="brand-subtitle">Cuisine</div>
            </div>
            <nav class="sidebar-nav">
                <div class="nav-item"><a class="nav-link" href="dashboard.php"><span class="nav-icon"><i class="bi bi-speedometer2"></i></span><span>Dashboard</span></a></div>
                <div class="nav-item"><a class="nav-link" href="commandes.php"><span class="nav-icon"><i class="bi bi-receipt"></i></span><span>Commandes</span></a></div>
                <div class="nav-item"><a class="nav-link active" href="parametres.php"><span class="nav-icon"><i class="bi bi-gear"></i></span><span>Paramètres</span></a></div>
            </nav>
            <div class="sidebar-footer"><?php dashboard_sidebar_user_footer('cuisinier'); ?></div>
        </aside>
        <main class="dashboard-main">
            <header class="dashboard-header">
                <div class="header-title">
                    <span class="header-eyebrow">Cuisine</span>
                    <h1>Paramètres</h1>
                </div>
            </header>
            <div class="dashboard-card mb-4">
                <h3 class="card-title">Compte</h3>
                <p class="text-secondary mb-1"><strong><?php echo htmlspecialchars($user['nom'] ?? ''); ?></strong></p>
                <p class="text-secondary small mb-0"><?php echo htmlspecialchars($user['email'] ?? ''); ?></p>
            </div>
            <div class="chart-container">
                <div class="chart-title">Contacts restaurant</div>
                <div class="contact-grid">
                    <div class="contact-card">
                        <h4><?php echo htmlspecialchars($contacts['nom'] ?? 'DynamoMenu'); ?></h4>
                        <p class="mb-1"><?php echo htmlspecialchars($contacts['adresse'] ?? ''); ?></p>
                        <p class="mb-1"><a href="tel:<?php echo htmlspecialchars(preg_replace('/\s+/', '', $contacts['telephone'] ?? '')); ?>"><?php echo htmlspecialchars($contacts['telephone'] ?? ''); ?></a></p>
                        <p class="mb-0"><a href="mailto:<?php echo htmlspecialchars($contacts['email'] ?? ''); ?>"><?php echo htmlspecialchars($contacts['email'] ?? ''); ?></a></p>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <?php dashboard_scripts(); ?>
</body>
</html>
