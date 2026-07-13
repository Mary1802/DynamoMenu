<?php

require_once __DIR__ . '/../bootstrap/app.php';

use App\Http\AdminPage;
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
    <?php Dashboard::assetLinks('Admin - Tableau de bord'); ?>
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

    <?php AdminPage::sidebar('dashboard'); ?>

    <div class="dashboard-shell">
        <main class="dashboard-main">
            <!-- Header -->
            <header class="dashboard-header">
                <div class="header-title">
                    <span class="header-eyebrow">Administration</span>
                    <h1>Tableau de bord</h1>
                    <p>Vue d'ensemble et statistiques de votre restaurant</p>
                </div>
                
                <div class="header-actions">
                    <div class="search-box">
                        <input type="search" class="search-input" data-dashboard-search placeholder="Client, n° commande…" aria-label="Rechercher">
                        <span class="search-icon"><i class="bi bi-search" aria-hidden="true"></i></span>
                    </div>
                    
                    <a href="notifications.php" class="notification-btn" aria-label="Notifications">
                        <i class="bi bi-bell" aria-hidden="true"></i>
                    </a>
                </div>
            </header>

            <!-- Indicateurs CA (horizontal) -->
            <div class="admin-metrics-row">
                <div class="metric-card dashboard-card metric-card--day">
                    <div class="metric-label">CA journalier</div>
                    <div class="metric-value"><?php echo Money::format((float) $stats['revenue_day']); ?></div>
                    <div class="stat-change"><?php echo (int) $ca_jour['nb']; ?> facture(s)</div>
                </div>
                <div class="metric-card dashboard-card metric-card--month">
                    <div class="metric-label">CA mensuel</div>
                    <div class="metric-value"><?php echo Money::format((float) $stats['revenue_month']); ?></div>
                    <div class="stat-change"><?php echo (int) $ca_mois['nb']; ?> facture(s)</div>
                </div>
                <div class="metric-card dashboard-card metric-card--clients">
                    <div class="metric-label">Clients</div>
                    <div class="metric-value"><?php echo (int) $stats['active_clients']; ?></div>
                </div>
                <div class="metric-card dashboard-card metric-card--orders">
                    <div class="metric-label">Commandes totales</div>
                    <div class="metric-value"><?php echo (int) $stats['total_orders']; ?></div>
                </div>
            </div>

            <!-- Contenu principal en deux colonnes -->
            <div class="row g-4">
                <!-- Colonne gauche -->
                <div class="col-lg-8">
                    <!-- Commandes récentes -->
                    <div class="chart-container">
                        <div class="chart-title">Commandes récentes</div>
                        <div class="compact-table table-responsive-wrap">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>N° Commande</th>
                                        <th>Client</th>
                                        <th>Montant</th>
                                        <th>Statut</th>
                                        <th>Heure</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recent_orders)): ?>
                                    <tr>
                                        <td colspan="5" style="text-align: center; padding: 1.5rem; color: var(--text-muted);">
                                            Aucune commande récente
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($recent_orders as $order): ?>
                                    <tr data-searchable data-search="<?php echo htmlspecialchars(mb_strtolower(($order['nom_client'] ?? '') . ' ' . $order['num_commande'])); ?>">
                                        <td>#<?php echo str_pad($order['num_commande'], 5, '0', STR_PAD_LEFT); ?></td>
                                        <td><?php echo htmlspecialchars($order['nom_client'] ?? 'Client'); ?></td>
                                        <td><?php echo Money::format((float) $order['montant_total']); ?></td>
                                        <td>
                                            <?php
                                            $status_labels = [
                                                'en_attente' => ['En attente', 'status-en-attente'],
                                                'en_preparation' => ['En préparation', 'status-en-preparation'],
                                                'prete' => ['Prête', 'status-prete'],
                                                'livree' => ['Livrée', 'status-livree'],
                                                'annulee' => ['Annulée', 'status-annulee'],
                                            ];
                                            $st = $status_labels[$order['statut']] ?? [htmlspecialchars($order['statut']), ''];
                                            ?>
                                            <span class="order-status <?php echo $st[1]; ?>"><?php echo $st[0]; ?></span>
                                        </td>
                                        <td><?php echo date('H:i', strtotime($order['date_commande'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Colonne droite -->
                <div class="col-lg-4">
                    <!-- Meilleurs plats -->
                    <div class="chart-container">
                        <div class="chart-title">Meilleurs plats</div>
                        <div class="compact-table table-responsive-wrap">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Plat</th>
                                        <th>Ventes</th>
                                        <th>Revenu</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($top_plats)): ?>
                                    <tr>
                                        <td colspan="3" style="text-align: center; padding: 1.5rem; color: var(--text-muted);">
                                            Aucune donnée disponible
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($top_plats as $plat): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($plat['nom_plat']); ?></td>
                                        <td><?php echo $plat['ventes']; ?></td>
                                        <td><?php echo Money::format((float) $plat['revenu']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Statistiques de statut -->
                    <div class="chart-container">
                        <div class="chart-title">Statut des commandes</div>
                        <div class="py-2">
                            <div class="progress-row">
                                <div class="progress-row-header">
                                    <span>En attente</span>
                                    <span><?php echo (int) $stats['pending_orders']; ?></span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo ($stats['pending_orders'] / max($stats['total_orders'], 1)) * 100; ?>%"></div>
                                </div>
                            </div>
                            <div class="progress-row">
                                <div class="progress-row-header">
                                    <span>En préparation</span>
                                    <span><?php echo (int) $stats['preparing_orders']; ?></span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo ($stats['preparing_orders'] / max($stats['total_orders'], 1)) * 100; ?>%"></div>
                                </div>
                            </div>
                            <div class="progress-row mb-0">
                                <div class="progress-row-header">
                                    <span>Prêtes</span>
                                    <span><?php echo (int) $stats['ready_orders']; ?></span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo ($stats['ready_orders'] / max($stats['total_orders'], 1)) * 100; ?>%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Séparateur -->
            <div class="section-divider"></div>

            <!-- Actions rapides - Section séparée -->
            <div>
                <h2 class="section-heading">Actions rapides</h2>
                
                <div class="quick-actions">
                    <a href="commandes.php" class="action-btn">
                        <div class="action-icon"><i class="bi bi-receipt" aria-hidden="true"></i></div>
                        <div class="action-content">
                            <h4>Gérer les commandes</h4>
                            <p>Voir et gérer toutes les commandes</p>
                        </div>
                    </a>
                    
                    <a href="plats.php" class="action-btn">
                        <div class="action-icon"><i class="bi bi-grid" aria-hidden="true"></i></div>
                        <div class="action-content">
                            <h4>Modifier le menu</h4>
                            <p>Ajouter ou modifier des plats</p>
                        </div>
                    </a>
                    
                    <a href="employes.php" class="action-btn">
                        <div class="action-icon"><i class="bi bi-people" aria-hidden="true"></i></div>
                        <div class="action-content">
                            <h4>Gérer le personnel</h4>
                            <p>Ajouter ou modifier des employés</p>
                        </div>
                    </a>
                    
                    <a href="rapports.php" class="action-btn">
                        <div class="action-icon"><i class="bi bi-file-earmark-bar-graph" aria-hidden="true"></i></div>
                        <div class="action-content">
                            <h4>Rapports ventes</h4>
                            <p>Journalier et mensuel (cash / mobile)</p>
                        </div>
                    </a>
                    
                    <a href="parametres.php#contacts-admin" class="action-btn">
                        <div class="action-icon"><i class="bi bi-telephone" aria-hidden="true"></i></div>
                        <div class="action-content">
                            <h4>Contact</h4>
                            <p>Coordonnées du restaurant</p>
                        </div>
                    </a>
                </div>
            </div>
        </main>
    </div>

    <?php Dashboard::scripts(); ?>
</body>
</html>