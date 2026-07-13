<?php

require_once __DIR__ . '/../bootstrap/app.php';

use App\Http\Dashboard;
use App\Http\Kernel;
use App\Support\Money;

$result = Kernel::forFile(__FILE__);
if ($result !== null) {
    extract($result, EXTR_SKIP);
}
?>
<!doctype html>
<html lang="fr">
<head>
    <?php Dashboard::assetLinks('Manager - Dashboard'); ?>
</head>
<body class="dashboard-body">
    <div class="sidebar-backdrop" id="sidebarBackdrop" aria-hidden="true"></div>

    <header class="dashboard-topbar">
        <button type="button" class="dashboard-menu-toggle" id="sidebarToggle" aria-label="Ouvrir le menu" aria-expanded="false" aria-controls="dashboardSidebar">
            <i class="bi bi-list" aria-hidden="true"></i>
        </button>
        <div class="dashboard-topbar-brand">Dynamo<span>Menu</span></div>
        <div style="width: 42px;"></div>
    </header>

    <aside class="dashboard-sidebar d-flex flex-column" id="dashboardSidebar">
            <div class="sidebar-brand">
                <div class="brand-logo">DM</div>
                <div class="brand-title">DynamoMenu</div>
                <div class="brand-subtitle">Service</div>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-item">
                    <a class="nav-link active" href="dashboard.php">
                        <span class="nav-icon"><i class="bi bi-speedometer2" aria-hidden="true"></i></span>
                        <span>Dashboard</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link" href="commandes.php">
                        <span class="nav-icon"><i class="bi bi-receipt" aria-hidden="true"></i></span>
                        <span>Commandes</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link" href="parametres.php">
                        <span class="nav-icon"><i class="bi bi-gear" aria-hidden="true"></i></span>
                        <span>Paramètres</span>
                    </a>
                </div>
            </nav>

            <div class="sidebar-footer">
                <?php Dashboard::sidebarUserFooter('manager'); ?>
            </div>
        </aside>

    <div class="dashboard-shell">
        <main class="dashboard-main">
            <header class="dashboard-header dashboard-header--kitchen">
                <div class="header-title">
                    <span class="header-eyebrow">Service & livraison</span>
                    <h1>Bonjour, <?php echo htmlspecialchars($_SESSION['nom'] ?? 'Manager'); ?></h1>
                    <p>Coordonnez la livraison des commandes prêtes vers les tables</p>
                </div>

                <div class="header-actions">
                    <div class="header-actions-top">
                        <?php Dashboard::renderNotifications('manager', $notif_items, $notif_count); ?>
                    </div>
                    <div class="search-box search-box--mobile-visible">
                        <input type="search" class="search-input" data-dashboard-search placeholder="N°, client, tél., table…" aria-label="Rechercher une commande">
                        <span class="search-icon"><i class="bi bi-search" aria-hidden="true"></i></span>
                    </div>
                </div>
            </header>

            <?php if ($dashboard_error): ?>
            <div class="success-message" style="color: var(--danger-color); border-color: rgba(220,53,69,0.35); background: rgba(220,53,69,0.1);">
                <?php echo htmlspecialchars($dashboard_error); ?>
            </div>
            <?php endif; ?>

            <div class="cuisine-stats">
                <div class="dashboard-card stat-card">
                    <div class="stat-icon warning"><i class="bi bi-bell" aria-hidden="true"></i></div>
                    <div class="stat-value"><?php echo (int) $stats['prete']; ?></div>
                    <div class="stat-label">À livrer</div>
                </div>
                <div class="dashboard-card stat-card">
                    <div class="stat-icon success"><i class="bi bi-check2-all" aria-hidden="true"></i></div>
                    <div class="stat-value"><?php echo (int) $stats['livree']; ?></div>
                    <div class="stat-label">Livrées (total)</div>
                </div>
            </div>

            <div class="dashboard-card kitchen-panel-card">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">Commandes prêtes à livrer</h3>
                        <p class="card-subtitle">Détail complet — assignez la livraison aux serveurs puis validez</p>
                    </div>
                    <a href="commandes.php" class="card-action">Recherche avancée</a>
                </div>

                <div class="order-timeline order-scroll-panel kitchen-scroll-panel">
                    <?php if (empty($commandes_pretes)): ?>
                        <div class="empty-state">
                            <div class="empty-icon"><i class="bi bi-inbox" aria-hidden="true"></i></div>
                            <h4>Aucune commande en attente de livraison</h4>
                            <p>Les alertes apparaîtront ici dès qu'une commande sera prête en cuisine.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($commandes_pretes as $commande): ?>
                            <div class="order-card" id="cmd-<?php echo (int) $commande['num_commande']; ?>" data-searchable data-search="<?php echo htmlspecialchars(Dashboard::orderSearchBlob($commande)); ?>">
                                <div class="order-header">
                                    <div>
                                        <div class="order-time">
                                            <?php
                                            $time = strtotime($commande['date_commande']);
                                            $elapsed = time() - $time;
                                            $mins = (int) floor($elapsed / 60);
                                            echo $mins > 0 ? 'Il y a ' . $mins . ' minute' . ($mins > 1 ? 's' : '') : 'À l\'instant';
                                            ?>
                                        </div>
                                        <div class="order-id">Commande #<?php echo str_pad((string) $commande['num_commande'], 5, '0', STR_PAD_LEFT); ?></div>
                                    </div>
                                    <span class="order-status status-prete">Prête</span>
                                </div>

                                <div class="order-details">
                                    <span class="order-meta"><i class="bi bi-table" aria-hidden="true"></i> Table <?php echo htmlspecialchars((string) ($commande['num_table'] ?? '—')); ?></span>
                                    <span class="order-meta"><i class="bi bi-box-seam" aria-hidden="true"></i> <?php echo (int) $commande['nombre_items']; ?> article(s)</span>
                                    <span class="order-meta"><?php echo Money::format((float) $commande['montant_total']); ?></span>
                                    <?php if (!empty($commande['nom_client']) || !empty($commande['prenom_client'])): ?>
                                    <span class="order-meta"><i class="bi bi-person" aria-hidden="true"></i> <?php echo htmlspecialchars(trim(($commande['prenom_client'] ?? '') . ' ' . ($commande['nom_client'] ?? ''))); ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($commande['telephone_client'])): ?>
                                    <span class="order-meta"><i class="bi bi-telephone" aria-hidden="true"></i> <?php echo htmlspecialchars((string) $commande['telephone_client']); ?></span>
                                    <?php endif; ?>
                                </div>

                                <?php Dashboard::renderKitchenInstructions($commande['instructions_speciales'] ?? null); ?>

                                <div class="order-items kitchen-order-items">
                                    <?php Dashboard::renderKitchenOrderDetails($commande['lignes'] ?? []); ?>
                                </div>

                                <div class="order-actions">
                                    <form method="POST" class="w-100">
                                        <?php Dashboard::csrfField(); ?>
                                        <input type="hidden" name="action" value="livree">
                                        <input type="hidden" name="commande_id" value="<?php echo (int) $commande['num_commande']; ?>">
                                        <button type="submit" class="btn-primary btn-success-variant w-100">
                                            <i class="bi bi-check2-all" aria-hidden="true"></i>
                                            <span>Marquer comme livrée</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <?php Dashboard::scripts(); ?>
</body>
</html>
