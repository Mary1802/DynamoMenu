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

contient_ensure_schema($pdo);

$statut_labels = [
    'en_attente' => 'En attente',
    'en_preparation' => 'En préparation',
    'prete' => 'Prête',
    'livree' => 'Livrée',
    'annulee' => 'Annulée',
];

$commandes_a_encaisser = [];
$commandes_payees = [];
$dashboard_error = null;

$commandeCols = array_column($pdo->query('SHOW COLUMNS FROM commande')->fetchAll(PDO::FETCH_ASSOC), 'Field');
$modeSouhaiteSql = in_array('mode_paiement_souhaite', $commandeCols, true) ? 'c.mode_paiement_souhaite,' : '';

try {
    $stmt = $pdo->query("
        SELECT c.num_commande, c.date_commande, c.montant_total, c.statut, c.num_table,
               {$modeSouhaiteSql}
               cl.nom_client, cl.prenom_client, cl.telephone_client
        FROM commande c
        LEFT JOIN client cl ON c.id_client = cl.id_client
        WHERE c.statut = 'livree'
          AND NOT EXISTS (SELECT 1 FROM facture f WHERE f.num_commande = c.num_commande)
        ORDER BY c.date_commande ASC
    ");
    $commandes_a_encaisser = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->query("
        SELECT c.num_commande, c.date_commande, c.montant_total, c.statut, c.num_table,
               {$modeSouhaiteSql}
               cl.nom_client, cl.prenom_client, cl.telephone_client,
               f.num_facture, f.total_paye, f.mode_paiement, f.date_facture AS date_paiement
        FROM facture f
        JOIN commande c ON f.num_commande = c.num_commande
        LEFT JOIN client cl ON c.id_client = cl.id_client
        ORDER BY f.date_facture DESC
        LIMIT 80
    ");
    $commandes_payees = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $dashboard_error = 'Impossible de charger les commandes : ' . $e->getMessage();
}

dashboard_attach_order_lines($pdo, $commandes_a_encaisser);
dashboard_attach_order_lines($pdo, $commandes_payees);
?>
<!doctype html>
<html lang="fr">
<head>
    <?php dashboard_asset_links('Caisse — Commandes'); ?>
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
            <header class="dashboard-header dashboard-header--kitchen">
                <div class="header-title">
                    <span class="header-eyebrow">Caisse</span>
                    <h1>Commandes</h1>
                    <p class="mb-0">Tri par statut de paiement</p>
                </div>
                <div class="header-actions">
                    <div class="search-box search-box--mobile-visible">
                        <input type="search" class="search-input" data-dashboard-search placeholder="Nom, tél., table, n° commande…" aria-label="Rechercher">
                        <span class="search-icon"><i class="bi bi-search"></i></span>
                    </div>
                </div>
            </header>

            <?php if (!empty($dashboard_error)): ?>
            <div class="success-message" style="color:var(--danger-color);"><?php echo htmlspecialchars($dashboard_error); ?></div>
            <?php endif; ?>

            <div class="row g-4 commandes-page-layout caissier-commandes-layout">
                <div class="col-lg-6">
                    <div class="dashboard-card commandes-filtrees-card">
                        <div class="card-header">
                            <h3 class="card-title">À encaisser</h3>
                            <span class="section-count">(<?php echo count($commandes_a_encaisser); ?>)</span>
                        </div>
                        <div class="commandes-filtrees-scroll order-scroll-panel kitchen-scroll-panel">
                            <?php if (empty($commandes_a_encaisser)): ?>
                            <div class="empty-state"><p>Aucune commande à encaisser.</p></div>
                            <?php else: ?>
                            <?php foreach ($commandes_a_encaisser as $c): ?>
                            <div class="commande-item mb-3" data-searchable data-search="<?php echo htmlspecialchars(dashboard_order_search_blob($c)); ?>">
                                <div class="commande-header">
                                    <div class="commande-id">#<?php echo str_pad((string) $c['num_commande'], 5, '0', STR_PAD_LEFT); ?></div>
                                    <div class="commande-montant"><?php echo format_money((float) $c['montant_total']); ?></div>
                                </div>
                                <div class="commande-detail-expanded mt-2">
                                    <?php dashboard_render_caissier_commande_detail($c, $statut_labels); ?>
                                </div>
                                <a href="paiement.php?voir_commande=<?php echo (int) $c['num_commande']; ?>" class="btn-payer btn-sm mt-2 d-inline-block">
                                    <i class="bi bi-cash-coin" aria-hidden="true"></i> Encaisser
                                </a>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="dashboard-card commandes-filtrees-card">
                        <div class="card-header">
                            <h3 class="card-title">Commandes payées</h3>
                            <span class="section-count">(<?php echo count($commandes_payees); ?>)</span>
                        </div>
                        <div class="commandes-filtrees-scroll order-scroll-panel kitchen-scroll-panel">
                            <?php if (empty($commandes_payees)): ?>
                            <div class="empty-state"><p>Aucune commande payée récente.</p></div>
                            <?php else: ?>
                            <?php foreach ($commandes_payees as $c): ?>
                            <div class="commande-item mb-3" data-searchable data-search="<?php echo htmlspecialchars(dashboard_order_search_blob($c) . ' ' . ($c['num_facture'] ?? '')); ?>">
                                <div class="commande-header">
                                    <div class="commande-id">
                                        #<?php echo str_pad((string) $c['num_commande'], 5, '0', STR_PAD_LEFT); ?>
                                        <span class="text-secondary small"> — Facture #<?php echo str_pad((string) $c['num_facture'], 4, '0', STR_PAD_LEFT); ?></span>
                                    </div>
                                    <div class="commande-montant"><?php echo format_money((float) ($c['total_paye'] ?? $c['montant_total'])); ?></div>
                                </div>
                                <div class="commande-detail-expanded mt-2">
                                    <?php dashboard_render_caissier_commande_detail($c, $statut_labels); ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <?php dashboard_scripts(); ?>
</body>
</html>
