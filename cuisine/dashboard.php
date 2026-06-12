<?php

require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/../includes/staff_auth.php';
require_once __DIR__ . '/../includes/money.php';
require_once __DIR__ . '/../includes/dashboard_helpers.php';

use App\Controller\Cuisine\KitchenDashboardController;

staff_require(['cuisinier']);

$kitchen = new KitchenDashboardController();
$kitchen->handlePost($_POST);
$data = $kitchen->index();

$stats = $data['stats'];
$commandes_actives = $data['commandes_actives'];
$commandes_terminees = $data['commandes_terminees'];
$dashboard_error = $data['dashboard_error'];
$notif_count = $data['notif_count'];
$notif_items = $data['notif_items'];
?>

<!doctype html>
<html lang="fr">
<head>
    <?php dashboard_asset_links('Cuisinier - Dashboard'); ?>
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

    <div class="dashboard-shell">
        <aside class="dashboard-sidebar d-flex flex-column" id="dashboardSidebar">
            <div class="sidebar-brand">
                <div class="brand-logo">DM</div>
                <div class="brand-title">DynamoMenu</div>
                <div class="brand-subtitle">Cuisine</div>
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
                <?php dashboard_sidebar_user_footer('cuisinier'); ?>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="dashboard-main">
            <!-- Header -->
            <header class="dashboard-header dashboard-header--kitchen">
                <div class="header-title">
                    <span class="header-eyebrow">Cuisine</span>
                    <h1>Bonjour, <?php echo htmlspecialchars($_SESSION['nom'] ?? 'Cuisinier'); ?></h1>
                    <p>Gérez les commandes en temps réel</p>
                </div>
                
                <div class="header-actions">
                    <div class="header-actions-top">
                        <?php dashboard_render_notifications('cuisinier', $notif_items, $notif_count); ?>
                    </div>
                    <div class="search-box search-box--mobile-visible">
                        <input type="search" class="search-input" data-dashboard-search placeholder="Nom, tél., table, n° commande…" aria-label="Rechercher une commande">
                        <span class="search-icon"><i class="bi bi-search" aria-hidden="true"></i></span>
                    </div>
                </div>
            </header>

            <?php if ($dashboard_error): ?>
            <div class="success-message" style="color: var(--danger-color); border-color: rgba(220,53,69,0.35); background: rgba(220,53,69,0.1);">
                <?php echo htmlspecialchars($dashboard_error); ?>
                <div class="mt-2">
                    <a href="../init_db.php" class="link-invoice">init_db.php</a>
                    ·
                    <a href="../run_update.php" class="link-invoice">run_update.php</a>
                </div>
            </div>
            <?php endif; ?>

            <!-- Stats (horizontal) -->
            <div class="cuisine-stats">
                <div class="dashboard-card stat-card">
                    <div class="stat-icon warning"><i class="bi bi-hourglass-split" aria-hidden="true"></i></div>
                    <div class="stat-value"><?php echo (int) $stats['en_attente']; ?></div>
                    <div class="stat-label">En attente</div>
                </div>
                <div class="dashboard-card stat-card">
                    <div class="stat-icon primary"><i class="bi bi-fire" aria-hidden="true"></i></div>
                    <div class="stat-value"><?php echo (int) $stats['en_preparation']; ?></div>
                    <div class="stat-label">En préparation</div>
                </div>
                <div class="dashboard-card stat-card">
                    <div class="stat-icon success"><i class="bi bi-check-circle" aria-hidden="true"></i></div>
                    <div class="stat-value"><?php echo (int) $stats['prete']; ?></div>
                    <div class="stat-label">Prêtes à servir</div>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div class="row g-4 kitchen-orders-layout">
                <div class="col-lg-8">
                    <!-- Commandes en cours -->
                    <div class="dashboard-card kitchen-panel-card">
                        <div class="card-header">
                            <div>
                                <h3 class="card-title">Commandes en cours</h3>
                                <p class="card-subtitle">Détail complet pour la préparation</p>
                            </div>
                        </div>
                        
                        <div class="order-timeline order-scroll-panel kitchen-scroll-panel">
                            <?php if (empty($commandes_actives)): ?>
                                <div class="empty-state">
                                    <div class="empty-icon"><i class="bi bi-inbox" aria-hidden="true"></i></div>
                                    <h4>Aucune commande en attente</h4>
                                    <p>Toutes les commandes sont traitées.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($commandes_actives as $commande): ?>
                                    <div class="order-card" id="cmd-<?php echo (int) $commande['num_commande']; ?>" data-searchable data-search="<?php echo htmlspecialchars(dashboard_order_search_blob($commande)); ?>">
                                        <div class="order-header">
                                            <div>
                                                <div class="order-time">
                                                    <?php 
                                                    $time = strtotime($commande['date_commande']);
                                                    $elapsed = time() - $time;
                                                    $mins = floor($elapsed / 60);
                                                    echo $mins > 0 ? 'Il y a ' . $mins . ' minute' . ($mins > 1 ? 's' : '') : 'À l\'instant';
                                                    ?>
                                                </div>
                                                <div class="order-id">Commande #<?php echo str_pad($commande['num_commande'], 5, '0', STR_PAD_LEFT); ?></div>
                                            </div>
                                            <?php
                                            $statut_labels = [
                                                'en_attente' => ['En attente', 'status-en-attente'],
                                                'en_preparation' => ['En préparation', 'status-en-preparation'],
                                            ];
                                            $sl = $statut_labels[$commande['statut']] ?? [htmlspecialchars($commande['statut']), ''];
                                            ?>
                                            <span class="order-status <?php echo $sl[1]; ?>"><?php echo $sl[0]; ?></span>
                                        </div>
                                        
                                        <div class="order-details">
                                            <span class="order-meta"><i class="bi bi-table" aria-hidden="true"></i> Table <?php echo htmlspecialchars((string) ($commande['num_table'] ?? '—')); ?></span>
                                            <span class="order-meta"><i class="bi bi-box-seam" aria-hidden="true"></i> <?php echo (int) $commande['nombre_items']; ?> article(s)</span>
                                            <span class="order-meta"><?php echo format_money((float) $commande['montant_total']); ?></span>
                                            <?php if (!empty($commande['nom_client']) || !empty($commande['prenom_client'])): ?>
                                            <span class="order-meta"><i class="bi bi-person" aria-hidden="true"></i> <?php echo htmlspecialchars(trim(($commande['prenom_client'] ?? '') . ' ' . ($commande['nom_client'] ?? ''))); ?></span>
                                            <?php endif; ?>
                                            <?php if ($commande['nombre_items'] > 3): ?>
                                                <span class="priority-badge">Priorité</span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php dashboard_render_kitchen_instructions($commande['instructions_speciales'] ?? null); ?>
                                        
                                        <div class="order-items kitchen-order-items">
                                            <?php dashboard_render_kitchen_order_details($commande['lignes'] ?? []); ?>
                                        </div>
                                        
                                        <div class="order-actions">
                                            <?php if ($commande['statut'] === 'en_attente'): ?>
                                                <form method="POST" class="w-100">
                                                    <input type="hidden" name="action" value="en_cours">
                                                    <input type="hidden" name="commande_id" value="<?php echo $commande['num_commande']; ?>">
                                                    <button type="submit" class="btn-primary w-100">
                                                        <i class="bi bi-play-fill" aria-hidden="true"></i>
                                                        <span>Commencer la préparation</span>
                                                    </button>
                                                </form>
                                            <?php elseif ($commande['statut'] === 'en_preparation'): ?>
                                                <form method="POST" class="w-100">
                                                    <input type="hidden" name="action" value="termine">
                                                    <input type="hidden" name="commande_id" value="<?php echo $commande['num_commande']; ?>">
                                                    <button type="submit" class="btn-primary btn-success-variant w-100">
                                                        <i class="bi bi-check-lg" aria-hidden="true"></i>
                                                        <span>Marquer comme terminé</span>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <!-- À servir -->
                    <div class="dashboard-card kitchen-panel-card">
                        <div class="card-header">
                            <h3 class="card-title">À servir</h3>
                            <a href="commandes.php?filtre=prete" class="card-action">Voir tout</a>
                        </div>
                        
                        <?php if (empty($commandes_terminees)): ?>
                            <div class="empty-state kitchen-scroll-panel">
                                <div class="empty-icon"><i class="bi bi-check2-circle" aria-hidden="true"></i></div>
                                <p>Aucune commande prête à servir</p>
                            </div>
                        <?php else: ?>
                            <div class="order-timeline order-scroll-panel kitchen-scroll-panel">
                                <?php foreach ($commandes_terminees as $cmd): ?>
                                    <div class="order-card" style="margin-bottom: 0.75rem;" data-searchable data-search="<?php echo htmlspecialchars(dashboard_order_search_blob($cmd)); ?>">
                                        <div class="order-header">
                                            <div class="order-id">#<?php echo str_pad($cmd['num_commande'],5,'0',STR_PAD_LEFT); ?></div>
                                            <span class="order-status status-prete">Prêt</span>
                                        </div>
                                        <div class="order-details">
                                            <span class="order-meta"><i class="bi bi-table" aria-hidden="true"></i> Table <?php echo htmlspecialchars((string) ($cmd['num_table'] ?? '—')); ?></span>
                                            <span class="order-meta"><i class="bi bi-box-seam" aria-hidden="true"></i> <?php echo (int) $cmd['nombre_items']; ?> article(s)</span>
                                        </div>
                                        <?php dashboard_render_kitchen_instructions($cmd['instructions_speciales'] ?? null); ?>
                                        <div class="order-items kitchen-order-items">
                                            <?php dashboard_render_kitchen_order_details($cmd['lignes'] ?? []); ?>
                                        </div>
                                        <div class="order-actions">
                                            <form method="POST" class="w-100">
                                                <input type="hidden" name="action" value="livree">
                                                <input type="hidden" name="commande_id" value="<?php echo $cmd['num_commande']; ?>">
                                                <button type="submit" class="btn-outline w-100">
                                                    <i class="bi bi-check2-all" aria-hidden="true"></i>
                                                    <span>Marquer comme livrée</span>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <?php dashboard_scripts(); ?>
</body>
</html>
