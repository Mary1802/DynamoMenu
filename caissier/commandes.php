<?php

require_once __DIR__ . '/../includes/staff_auth.php';
staff_require(['caissier']);

$db_config = require '../config/db.php';
require_once __DIR__ . '/../includes/money.php';
require_once __DIR__ . '/../includes/dashboard_helpers.php';

$pdo = new PDO(
    "mysql:host={$db_config['host']};dbname={$db_config['dbname']}",
    $db_config['user'],
    $db_config['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$stmt = $pdo->query("
    SELECT c.num_commande, c.date_commande, c.montant_total, c.statut, c.num_table,
           cl.nom_client, cl.prenom_client, cl.telephone_client,
           (SELECT COUNT(*) FROM facture f WHERE f.num_commande = c.num_commande) AS payee
    FROM commande c
    LEFT JOIN client cl ON c.id_client = cl.id_client
    WHERE c.statut IN ('livree', 'prete')
    ORDER BY c.date_commande DESC
    LIMIT 100
");
$commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="fr">
<head>
    <?php dashboard_asset_links('Caisse — Commandes'); ?>
</head>
<body class="dashboard-body">
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
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
                <div class="brand-subtitle">Caisse</div>
            </div>
            <nav class="sidebar-nav">
                <div class="nav-item"><a class="nav-link" href="paiement.php"><span class="nav-icon"><i class="bi bi-credit-card"></i></span><span>Paiements</span></a></div>
                <div class="nav-item"><a class="nav-link active" href="commandes.php"><span class="nav-icon"><i class="bi bi-receipt"></i></span><span>Commandes</span></a></div>
                <div class="nav-item"><a class="nav-link" href="rapports.php"><span class="nav-icon"><i class="bi bi-file-earmark-bar-graph"></i></span><span>Rapports</span></a></div>
                <div class="nav-item"><a class="nav-link" href="parametres.php"><span class="nav-icon"><i class="bi bi-gear"></i></span><span>Paramètres</span></a></div>
            </nav>
            <div class="sidebar-footer"><?php dashboard_sidebar_user_footer('caissier'); ?></div>
        </aside>
        <main class="dashboard-main">
            <header class="dashboard-header">
                <div class="header-title">
                    <span class="header-eyebrow">Caisse</span>
                    <h1>Commandes</h1>
                </div>
                <div class="header-actions">
                    <div class="search-box">
                        <input type="search" class="search-input" data-dashboard-search placeholder="Nom, tél., table…" aria-label="Rechercher">
                        <span class="search-icon"><i class="bi bi-search"></i></span>
                    </div>
                </div>
            </header>
            <div class="commandes-section">
                <?php foreach ($commandes as $c): ?>
                <div class="commande-item" data-searchable data-search="<?php echo htmlspecialchars(dashboard_order_search_blob($c)); ?>">
                    <div class="commande-header">
                        <div class="commande-id">#<?php echo str_pad((string) $c['num_commande'], 5, '0', STR_PAD_LEFT); ?> — Table <?php echo htmlspecialchars((string) ($c['num_table'] ?? '—')); ?></div>
                        <div class="commande-montant"><?php echo format_money((float) $c['montant_total']); ?></div>
                    </div>
                    <div class="commande-details">
                        <span><?php echo htmlspecialchars(trim(($c['prenom_client'] ?? '') . ' ' . ($c['nom_client'] ?? ''))); ?></span>
                        <span><?php echo (int) $c['payee'] ? 'Payée' : 'À encaisser'; ?></span>
                    </div>
                    <?php if (!(int) $c['payee']): ?>
                    <a href="paiement.php?voir_commande=<?php echo (int) $c['num_commande']; ?>" class="btn-payer btn-sm mt-2 d-inline-block">Encaisser</a>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>
    <?php dashboard_scripts(); ?>
</body>
</html>
