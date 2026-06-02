<?php

require_once __DIR__ . '/dashboard_helpers.php';
require_once __DIR__ . '/staff_auth.php';

function admin_require_auth(): array
{
    return staff_require(['admin'], '../login.php');
}

function admin_pdo(): PDO
{
    $db_config = require dirname(__DIR__) . '/config/db.php';

    return new PDO(
        'mysql:host=' . $db_config['host'] . ';dbname=' . $db_config['dbname'],
        $db_config['user'],
        $db_config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
}

/** @param array<string, string> $items slug => label */
function admin_sidebar(string $active, array $items = []): void
{
    if ($items === []) {
        $items = [
            'dashboard' => ['url' => 'dashboard.php', 'icon' => 'bi-speedometer2', 'label' => 'Dashboard'],
            'tables' => ['url' => 'tables.php', 'icon' => 'bi-qr-code', 'label' => 'Tables & QR'],
            'commandes' => ['url' => 'commandes.php', 'icon' => 'bi-receipt', 'label' => 'Commandes'],
            'plats' => ['url' => 'plats.php', 'icon' => 'bi-grid', 'label' => 'Menu (plats)'],
            'clients' => ['url' => 'clients.php', 'icon' => 'bi-people', 'label' => 'Clients'],
            'fidelite' => ['url' => 'fidelite.php', 'icon' => 'bi-gift', 'label' => 'Fidélité'],
            'notifications' => ['url' => 'notifications.php', 'icon' => 'bi-bell', 'label' => 'Notifications'],
            'employes' => ['url' => 'employes.php', 'icon' => 'bi-person-badge', 'label' => 'Employés'],
            'rapports' => ['url' => 'rapports.php', 'icon' => 'bi-file-earmark-bar-graph', 'label' => 'Rapports ventes'],
            'stats' => ['url' => 'stats.php', 'icon' => 'bi-graph-up', 'label' => 'Statistiques'],
            'contact' => ['url' => 'contact.php', 'icon' => 'bi-telephone', 'label' => 'Contact'],
            'logs' => ['url' => 'logs.php', 'icon' => 'bi-journal-text', 'label' => 'Journaux'],
        ];
    }
    ?>
    <aside class="dashboard-sidebar d-flex flex-column" id="dashboardSidebar">
        <div class="sidebar-brand">
            <div class="brand-logo">DM</div>
            <div class="brand-title">DynamoMenu</div>
            <div class="brand-subtitle">Administration</div>
        </div>
        <nav class="sidebar-nav">
            <?php foreach ($items as $slug => $item): ?>
            <div class="nav-item">
                <a class="nav-link<?php echo $slug === $active ? ' active' : ''; ?>" href="<?php echo htmlspecialchars($item['url']); ?>">
                    <span class="nav-icon"><i class="bi <?php echo htmlspecialchars($item['icon']); ?>" aria-hidden="true"></i></span>
                    <span><?php echo htmlspecialchars($item['label']); ?></span>
                </a>
            </div>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-footer">
            <?php dashboard_sidebar_user_footer('admin'); ?>
        </div>
    </aside>
    <?php
}

function admin_shell_start(string $title, string $active, string $eyebrow, string $heading, string $subtitle = ''): void
{
    admin_require_auth();
    ?>
<!doctype html>
<html lang="fr">
<head>
    <?php dashboard_asset_links($title); ?>
</head>
<body class="dashboard-body">
    <div class="sidebar-backdrop" id="sidebarBackdrop" aria-hidden="true"></div>
    <header class="dashboard-topbar">
        <button type="button" class="dashboard-menu-toggle" id="sidebarToggle" aria-label="Menu"><i class="bi bi-list"></i></button>
        <div class="dashboard-topbar-brand">Dynamo<span>Menu</span></div>
        <div style="width:42px;"></div>
    </header>
    <div class="dashboard-shell">
        <?php admin_sidebar($active); ?>
        <main class="dashboard-main">
            <header class="dashboard-header">
                <div class="header-title">
                    <span class="header-eyebrow"><?php echo htmlspecialchars($eyebrow); ?></span>
                    <h1><?php echo htmlspecialchars($heading); ?></h1>
                    <?php if ($subtitle !== ''): ?><p><?php echo htmlspecialchars($subtitle); ?></p><?php endif; ?>
                </div>
            </header>
    <?php
}

function admin_shell_end(): void
{
    ?>
        </main>
    </div>
    <?php dashboard_scripts(); ?>
</body>
</html>
    <?php
}

function admin_log(PDO $pdo, string $action, string $description, string $module = 'admin'): void
{
    try {
        $stmt = $pdo->prepare('INSERT INTO log_activite (action, description, module_concerne) VALUES (?, ?, ?)');
        $stmt->execute([$action, $description, $module]);
    } catch (PDOException $e) {
        // ignore
    }
}
