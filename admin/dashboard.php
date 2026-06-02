<?php

require_once __DIR__ . '/../includes/staff_auth.php';
staff_require(['admin']);

// Configuration de la base de données
$db_config = require '../config/db.php';
try {
    $pdo = new PDO(
        "mysql:host=" . $db_config['host'] . ";dbname=" . $db_config['dbname'],
        $db_config['user'],
        $db_config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die('Erreur de connexion: ' . $e->getMessage());
}

require_once __DIR__ . '/../includes/dashboard_helpers.php';
require_once __DIR__ . '/../includes/money.php';

$jour = date('Y-m-d');
$mois = date('Y-m');
$ca_jour = dashboard_sales_totals($pdo, 'day', $jour);
$ca_mois = dashboard_sales_totals($pdo, 'month', $mois);

// Récupérer les statistiques
$stats = [
    'total_orders' => $pdo->query("SELECT COUNT(*) FROM commande")->fetchColumn(),
    'total_revenue' => $ca_mois['ca'],
    'revenue_day' => $ca_jour['ca'],
    'revenue_month' => $ca_mois['ca'],
    'active_clients' => $pdo->query("SELECT COUNT(*) FROM client")->fetchColumn(),
    'pending_orders' => $pdo->query("SELECT COUNT(*) FROM commande WHERE statut = 'en_attente'")->fetchColumn(),
    'preparing_orders' => $pdo->query("SELECT COUNT(*) FROM commande WHERE statut = 'en_preparation'")->fetchColumn(),
    'ready_orders' => $pdo->query("SELECT COUNT(*) FROM commande WHERE statut = 'prete'")->fetchColumn(),
];

// Récupérer les commandes récentes (limité à 3 pour économiser l'espace)
$stmt = $pdo->prepare("
    SELECT c.*, cl.nom_client 
    FROM commande c
    LEFT JOIN client cl ON c.id_client = cl.id_client
    ORDER BY c.date_commande DESC
    LIMIT 3
");
$stmt->execute();
$recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les meilleurs plats (limité à 3)
$stmt = $pdo->prepare("
    SELECT p.nom_plat, COUNT(d.id_detail) as ventes, SUM(d.sous_total) as revenu
    FROM contient d
    JOIN plat p ON d.id_plat = p.id_plat
    GROUP BY p.id_plat, p.nom_plat
    ORDER BY ventes DESC
    LIMIT 3
");
$stmt->execute();
$top_plats = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="fr">
<head>
    <?php dashboard_asset_links('Admin - Tableau de bord'); ?>
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
                <div class="brand-subtitle">Administration</div>
            </div>
            
            <nav class="sidebar-nav">
                <div class="nav-item">
                    <a class="nav-link active" href="dashboard.php">
                        <span class="nav-icon"><i class="bi bi-speedometer2" aria-hidden="true"></i></span>
                        <span>Dashboard</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link" href="tables.php">
                        <span class="nav-icon"><i class="bi bi-qr-code" aria-hidden="true"></i></span>
                        <span>Tables & QR</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link" href="commandes.php">
                        <span class="nav-icon"><i class="bi bi-receipt" aria-hidden="true"></i></span>
                        <span>Commandes</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link" href="plats.php">
                        <span class="nav-icon"><i class="bi bi-grid" aria-hidden="true"></i></span>
                        <span>Menu</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link" href="clients.php">
                        <span class="nav-icon"><i class="bi bi-people" aria-hidden="true"></i></span>
                        <span>Clients</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link" href="fidelite.php">
                        <span class="nav-icon"><i class="bi bi-gift" aria-hidden="true"></i></span>
                        <span>Fidélité</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link" href="notifications.php">
                        <span class="nav-icon"><i class="bi bi-bell" aria-hidden="true"></i></span>
                        <span>Notifications</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link" href="employes.php">
                        <span class="nav-icon"><i class="bi bi-person-badge" aria-hidden="true"></i></span>
                        <span>Employés</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link" href="rapports.php">
                        <span class="nav-icon"><i class="bi bi-file-earmark-bar-graph" aria-hidden="true"></i></span>
                        <span>Rapports ventes</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link" href="stats.php">
                        <span class="nav-icon"><i class="bi bi-graph-up" aria-hidden="true"></i></span>
                        <span>Statistiques</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link" href="contact.php">
                        <span class="nav-icon"><i class="bi bi-telephone" aria-hidden="true"></i></span>
                        <span>Contact</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link" href="logs.php">
                        <span class="nav-icon"><i class="bi bi-journal-text" aria-hidden="true"></i></span>
                        <span>Journaux</span>
                    </a>
                </div>
            </nav>
            
            <div class="sidebar-footer">
                <?php dashboard_sidebar_user_footer('admin'); ?>
            </div>
        </aside>

        <!-- Main Content -->
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
                        <span class="notification-badge"><?php echo (int) $stats['pending_orders']; ?></span>
                    </a>
                </div>
            </header>

            <!-- Indicateurs CA (horizontal) -->
            <div class="admin-metrics-row">
                <div class="metric-card dashboard-card">
                    <div class="metric-label">CA journalier</div>
                    <div class="metric-value"><?php echo format_money((float) $stats['revenue_day']); ?></div>
                    <div class="stat-change"><?php echo (int) $ca_jour['nb']; ?> facture(s)</div>
                </div>
                <div class="metric-card dashboard-card">
                    <div class="metric-label">CA mensuel</div>
                    <div class="metric-value"><?php echo format_money((float) $stats['revenue_month']); ?></div>
                    <div class="stat-change"><?php echo (int) $ca_mois['nb']; ?> facture(s)</div>
                </div>
                <div class="metric-card dashboard-card">
                    <div class="metric-label">Clients</div>
                    <div class="metric-value"><?php echo (int) $stats['active_clients']; ?></div>
                </div>
                <div class="metric-card dashboard-card">
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
                                        <td><?php echo format_money((float) $order['montant_total']); ?></td>
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
                    
                    <!-- Graphique d'activité horaire -->
                    <div class="chart-container">
                        <div class="chart-title">Activité par heure (aujourd'hui)</div>
                        <div class="hourly-chart">
                            <?php 
                            // Simuler des données horaires pour l'exemple
                            $hours_data = [8, 12, 15, 18, 20, 22];
                            $max_value = 10;
                            
                            foreach ($hours_data as $hour): 
                                $height = (rand(3, 10) / $max_value) * 100;
                            ?>
                            <div class="hour-bar">
                                <div class="bar-value" style="height: <?php echo $height; ?>%"></div>
                                <div class="bar-label"><?php echo sprintf('%02d:00', $hour); ?></div>
                            </div>
                            <?php endforeach; ?>
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
                                        <td><?php echo format_money((float) $plat['revenu']); ?></td>
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
                    
                    <a href="contact.php" class="action-btn">
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

    <?php dashboard_scripts(); ?>
    <script>
        // Animation des barres du graphique horaire
        document.addEventListener('DOMContentLoaded', function() {
            const bars = document.querySelectorAll('.bar-value');
            bars.forEach((bar, index) => {
                setTimeout(() => {
                    bar.style.transition = 'height 0.8s ease';
                }, index * 100);
            });
        });
    </script>
</body>
</html>