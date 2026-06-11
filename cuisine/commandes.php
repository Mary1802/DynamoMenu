<?php

require_once __DIR__ . '/../includes/staff_auth.php';
staff_require(['cuisinier']);

$db_config = require '../config/db.php';
require_once __DIR__ . '/../includes/money.php';
require_once __DIR__ . '/../includes/dashboard_helpers.php';

$pdo = new PDO(
    "mysql:host={$db_config['host']};dbname={$db_config['dbname']}",
    $db_config['user'],
    $db_config['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$filtre = $_GET['filtre'] ?? 'actives';
$where = "c.statut IN ('en_attente', 'en_preparation')";
if ($filtre === 'prete') {
    $where = "c.statut = 'prete'";
} elseif ($filtre === 'toutes') {
    $where = "c.statut NOT IN ('annulee')";
}

$stmt = $pdo->query("
    SELECT c.num_commande, c.date_commande, c.montant_total, c.statut, c.num_table, c.instructions_speciales,
           cl.nom_client, cl.prenom_client, cl.telephone_client
    FROM commande c
    LEFT JOIN client cl ON c.id_client = cl.id_client
    WHERE {$where}
    ORDER BY c.date_commande DESC
    LIMIT 80
");
$commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);
dashboard_attach_order_lines($pdo, $commandes);

$stmt = $pdo->query("
    SELECT c.num_commande, c.date_commande, c.montant_total, c.statut, c.num_table, c.instructions_speciales,
           cl.nom_client, cl.prenom_client, cl.telephone_client
    FROM commande c
    LEFT JOIN client cl ON c.id_client = cl.id_client
    WHERE c.statut NOT IN ('annulee')
    ORDER BY c.date_commande DESC
    LIMIT 20
");
$commandes_recentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
dashboard_attach_order_lines($pdo, $commandes_recentes);

$statut_labels = [
    'en_attente' => 'En attente',
    'en_preparation' => 'En préparation',
    'prete' => 'Prête',
    'livree' => 'Livrée',
];
?>
<!doctype html>
<html lang="fr">
<head>
    <?php dashboard_asset_links('Cuisine — Commandes'); ?>
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
                <div class="brand-subtitle">Cuisine</div>
            </div>
            <nav class="sidebar-nav">
                <div class="nav-item"><a class="nav-link" href="dashboard.php"><span class="nav-icon"><i class="bi bi-speedometer2"></i></span><span>Dashboard</span></a></div>
                <div class="nav-item"><a class="nav-link active" href="commandes.php"><span class="nav-icon"><i class="bi bi-receipt"></i></span><span>Commandes</span></a></div>
                <div class="nav-item"><a class="nav-link" href="parametres.php"><span class="nav-icon"><i class="bi bi-gear"></i></span><span>Paramètres</span></a></div>
            </nav>
            <div class="sidebar-footer"><?php dashboard_sidebar_user_footer('cuisinier'); ?></div>
        </aside>
        <main class="dashboard-main">
            <header class="dashboard-header dashboard-header--kitchen">
                <div class="header-title">
                    <span class="header-eyebrow">Cuisine</span>
                    <h1>Toutes les commandes</h1>
                </div>
                <div class="header-actions">
                    <div class="search-box search-box--mobile-visible">
                        <input type="search" class="search-input" data-dashboard-search placeholder="Nom, tél., table…" aria-label="Rechercher">
                        <span class="search-icon"><i class="bi bi-search"></i></span>
                    </div>
                </div>
            </header>

            <div class="mb-3 d-flex flex-wrap gap-2">
                <a href="commandes.php" class="btn-details btn-sm<?php echo $filtre === 'actives' ? ' active' : ''; ?>">En cours</a>
                <a href="commandes.php?filtre=prete" class="btn-details btn-sm<?php echo $filtre === 'prete' ? ' active' : ''; ?>">À servir</a>
                <a href="commandes.php?filtre=toutes" class="btn-details btn-sm<?php echo $filtre === 'toutes' ? ' active' : ''; ?>">Toutes</a>
            </div>

            <div class="row g-4 commandes-page-layout">
                <div class="col-lg-8">
                    <div class="dashboard-card commandes-filtrees-card">
                        <div class="commandes-filtrees-scroll order-scroll-panel kitchen-scroll-panel">
                        <?php if (empty($commandes)): ?>
                        <div class="empty-state"><p>Aucune commande pour ce filtre.</p></div>
                        <?php else: ?>
                        <?php foreach ($commandes as $c): ?>
                        <div class="commande-item" data-searchable data-search="<?php echo htmlspecialchars(dashboard_order_search_blob($c)); ?>">
                            <div class="commande-header">
                                <div class="commande-id">#<?php echo str_pad((string) $c['num_commande'], 5, '0', STR_PAD_LEFT); ?> — Table <?php echo htmlspecialchars((string) ($c['num_table'] ?? '—')); ?></div>
                                <div class="commande-montant"><?php echo format_money((float) $c['montant_total']); ?></div>
                            </div>
                            <div class="commande-details">
                                <span><?php echo htmlspecialchars(trim(($c['prenom_client'] ?? '') . ' ' . ($c['nom_client'] ?? ''))); ?></span>
                                <span><?php echo htmlspecialchars($statut_labels[$c['statut']] ?? $c['statut']); ?></span>
                                <span><?php echo date('d/m H:i', strtotime($c['date_commande'])); ?></span>
                            </div>
                            <?php dashboard_render_kitchen_instructions($c['instructions_speciales'] ?? null); ?>
                            <div class="order-items kitchen-order-items mb-2">
                                <?php dashboard_render_kitchen_order_details($c['lignes'] ?? []); ?>
                            </div>
                            <?php if (($c['statut'] ?? '') !== 'livree'): ?>
                            <a href="dashboard.php#cmd-<?php echo (int) $c['num_commande']; ?>" class="btn-details btn-sm">Ouvrir sur le dashboard</a>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="dashboard-card commandes-recentes-panel">
                        <div class="card-header">
                            <h3 class="card-title">Commandes récentes</h3>
                        </div>
                        <?php if (empty($commandes_recentes)): ?>
                        <p class="text-secondary small mb-0">Aucune commande récente.</p>
                        <?php else: ?>
                        <?php foreach ($commandes_recentes as $r): ?>
                        <div class="commande-item" style="margin-bottom:0.65rem;" data-searchable data-search="<?php echo htmlspecialchars(dashboard_order_search_blob($r)); ?>">
                            <div class="commande-header">
                                <div class="commande-id" style="font-size:0.9rem;">#<?php echo str_pad((string) $r['num_commande'], 5, '0', STR_PAD_LEFT); ?></div>
                                <?php
                                $statusCss = [
                                    'en_attente' => 'status-en-attente',
                                    'en_preparation' => 'status-en-preparation',
                                    'prete' => 'status-prete',
                                    'livree' => 'status-livree',
                                ];
                                $sc = $statusCss[$r['statut']] ?? '';
                                ?>
                                <span class="order-status <?php echo $sc; ?>" style="font-size:0.65rem;">
                                    <?php echo htmlspecialchars($statut_labels[$r['statut']] ?? $r['statut']); ?>
                                </span>
                            </div>
                            <div class="commande-details" style="font-size:0.82rem;">
                                <span>Table <?php echo htmlspecialchars((string) ($r['num_table'] ?? '—')); ?></span>
                                <span><?php echo date('d/m H:i', strtotime($r['date_commande'])); ?></span>
                            </div>
                            <?php $detailId = 'detail-recent-' . (int) $r['num_commande']; ?>
                            <button type="button" class="btn-details btn-sm mt-1 btn-toggle-commande-detail" data-target="<?php echo $detailId; ?>" aria-expanded="false" aria-controls="<?php echo $detailId; ?>">
                                Voir
                            </button>
                            <div id="<?php echo $detailId; ?>" class="commande-detail-panel" hidden>
                                <?php dashboard_render_cuisine_commande_full_detail($r, $statut_labels); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <?php dashboard_scripts(); ?>
    <script>
    (function () {
        document.querySelectorAll('.btn-toggle-commande-detail').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id = btn.getAttribute('data-target');
                var panel = id ? document.getElementById(id) : null;
                if (!panel) return;
                var willOpen = panel.hidden;
                document.querySelectorAll('.commande-detail-panel').forEach(function (p) { p.hidden = true; });
                document.querySelectorAll('.btn-toggle-commande-detail').forEach(function (b) {
                    b.setAttribute('aria-expanded', 'false');
                    b.textContent = 'Voir';
                });
                if (willOpen) {
                    panel.hidden = false;
                    btn.setAttribute('aria-expanded', 'true');
                    btn.textContent = 'Masquer';
                    panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            });
        });
    })();
    </script>
</body>
</html>
