<?php
session_start();

// Vérifier l'authentification
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../client/index.php');
    exit;
}

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

// Récupérer les statistiques
$stats = [
    'total_orders' => $pdo->query("SELECT COUNT(*) FROM commande")->fetchColumn(),
    'total_revenue' => $pdo->query("SELECT COALESCE(SUM(montant_total), 0) FROM commande WHERE statut = 'prete'")->fetchColumn(),
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
    GROUP BY p.id_plat
    ORDER BY ventes DESC
    LIMIT 3
");
$stmt->execute();
$top_plats = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin - Dashboard Professionnel</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboards.css">
    <style>
        /* Styles spécifiques au dashboard admin */
        .admin-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .metric-card {
            background: linear-gradient(135deg, var(--panel-bg) 0%, #0e0e0f 100%);
            border: 1px solid var(--panel-border);
            border-radius: 16px;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
        }
        
        .metric-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
        }
        
        .metric-value {
            font-size: 2.2rem;
            font-weight: 800;
            color: var(--text-primary);
            margin: 0.5rem 0;
            line-height: 1;
        }
        
        .metric-label {
            font-size: 0.85rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 500;
        }
        
        .metric-change {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.8rem;
            font-weight: 500;
            margin-top: 0.5rem;
        }
        
        .metric-change.positive {
            color: var(--success-color);
        }
        
        .metric-change.negative {
            color: var(--danger-color);
        }
        
        .chart-container {
            background: linear-gradient(135deg, var(--panel-bg) 0%, #0e0e0f 100%);
            border: 1px solid var(--panel-border);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .chart-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 1rem;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th {
            text-align: left;
            padding: 0.75rem;
            border-bottom: 1px solid var(--panel-border);
            color: var(--text-muted);
            font-weight: 500;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .data-table td {
            padding: 0.75rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            color: var(--text-secondary);
            font-size: 0.85rem;
        }
        
        .data-table tr:hover {
            background: rgba(255, 255, 255, 0.02);
        }
        
        .data-table tr:last-child td {
            border-bottom: none;
        }
        
        .progress-bar {
            height: 6px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 3px;
            overflow: hidden;
            margin-top: 0.25rem;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            border-radius: 3px;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-top: 2rem;
        }
        
        @media (max-width: 1200px) {
            .quick-actions {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .quick-actions {
                grid-template-columns: 1fr;
            }
            
            .admin-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .action-btn {
            background: linear-gradient(135deg, var(--panel-bg) 0%, #0e0e0f 100%);
            border: 1px solid var(--panel-border);
            border-radius: 12px;
            padding: 1.25rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            text-decoration: none;
            color: var(--text-secondary);
            transition: all 0.2s ease;
            min-height: 90px;
        }
        
        .action-btn:hover {
            border-color: var(--primary-color);
            color: var(--text-primary);
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(255, 111, 31, 0.1);
        }
        
        .action-icon {
            width: 44px;
            height: 44px;
            min-width: 44px;
            border-radius: 10px;
            background: linear-gradient(135deg, rgba(255, 111, 31, 0.1), rgba(244, 201, 90, 0.05));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            color: var(--primary-color);
        }
        
        .action-content {
            flex: 1;
            min-width: 0;
        }
        
        .action-content h4 {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0 0 0.25rem 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .action-content p {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin: 0;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .hourly-chart {
            display: flex;
            align-items: flex-end;
            height: 150px;
            gap: 0.5rem;
            padding: 1rem 0;
        }
        
        .hour-bar {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .bar-value {
            background: linear-gradient(to top, var(--primary-color), var(--secondary-color));
            width: 100%;
            border-radius: 4px 4px 0 0;
            min-height: 4px;
        }
        
        .bar-label {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-top: 0.5rem;
        }
        
        .section-divider {
            height: 1px;
            background: var(--panel-border);
            margin: 2.5rem 0;
        }
        
        .compact-table {
            max-height: 250px;
            overflow-y: auto;
        }
        
        .compact-table::-webkit-scrollbar {
            width: 6px;
        }
        
        .compact-table::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 3px;
        }
        
        .compact-table::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 3px;
        }
    </style>
</head>
<body class="dashboard-body">
    <div class="d-flex">
        <!-- Sidebar -->
        <aside class="dashboard-sidebar d-flex flex-column">
            <div class="sidebar-brand">
                <div class="brand-logo">⚙️</div>
                <div class="brand-title">DynamoMenu</div>
                <div class="brand-subtitle">Administration</div>
            </div>
            
            <nav class="sidebar-nav">
                <div class="nav-item">
                    <a class="nav-link active" href="dashboard.php">
                        <span class="nav-icon">📊</span>
                        <span>Dashboard</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link" href="commandes.php">
                        <span class="nav-icon">📋</span>
                        <span>Commandes</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link" href="plats.php">
                        <span class="nav-icon">🍽️</span>
                        <span>Menu</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link" href="utilisateurs.php">
                        <span class="nav-icon">👥</span>
                        <span>Utilisateurs</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link" href="#">
                        <span class="nav-icon">📈</span>
                        <span>Analytics</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link" href="#">
                        <span class="nav-icon">⚙️</span>
                        <span>Paramètres</span>
                    </a>
                </div>
            </nav>
            
            <div class="sidebar-footer">
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo substr($_SESSION['nom'] ?? 'A', 0, 1); ?>
                    </div>
                    <div class="user-details">
                        <div class="user-name"><?php echo htmlspecialchars($_SESSION['nom'] ?? 'Administrateur'); ?></div>
                        <div class="user-role">Administrateur</div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="dashboard-main">
            <!-- Header -->
            <header class="dashboard-header">
                <div class="header-title">
                    <h1>Tableau de bord Administrateur</h1>
                    <p>Vue d'ensemble et statistiques de votre restaurant</p>
                </div>
                
                <div class="header-actions">
                    <div class="search-box">
                        <input type="text" class="search-input" placeholder="Rechercher...">
                        <span class="search-icon">🔍</span>
                    </div>
                    
                    <a href="#" class="notification-btn">
                        <span>🔔</span>
                        <span class="notification-badge">5</span>
                    </a>
                </div>
            </header>

            <!-- Metrics Grid -->
            <div class="admin-grid">
                <div class="metric-card">
                    <div class="metric-label">Commandes totales</div>
                    <div class="metric-value"><?php echo $stats['total_orders']; ?></div>
                    <div class="metric-change positive">
                        <span>↑</span>
                        <span>+12.5% ce mois</span>
                    </div>
                </div>
                
                <div class="metric-card">
                    <div class="metric-label">Chiffre d'affaires</div>
                    <div class="metric-value">€<?php echo number_format($stats['total_revenue'], 0, ',', ' '); ?></div>
                    <div class="metric-change positive">
                        <span>↑</span>
                        <span>+18.2% ce mois</span>
                    </div>
                </div>
                
                <div class="metric-card">
                    <div class="metric-label">Clients actifs</div>
                    <div class="metric-value"><?php echo $stats['active_clients']; ?></div>
                    <div class="metric-change positive">
                        <span>↑</span>
                        <span>+8% ce mois</span>
                    </div>
                </div>
                
                <div class="metric-card">
                    <div class="metric-label">Commandes en attente</div>
                    <div class="metric-value"><?php echo $stats['pending_orders']; ?></div>
                    <div class="metric-change negative">
                        <span>↓</span>
                        <span>-5% aujourd'hui</span>
                    </div>
                </div>
            </div>

            <!-- Contenu principal en deux colonnes -->
            <div class="row g-4">
                <!-- Colonne gauche -->
                <div class="col-lg-8">
                    <!-- Commandes récentes -->
                    <div class="chart-container">
                        <div class="chart-title">Commandes récentes</div>
                        <div class="compact-table">
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
                                    <tr>
                                        <td>#<?php echo str_pad($order['num_commande'], 5, '0', STR_PAD_LEFT); ?></td>
                                        <td><?php echo htmlspecialchars($order['nom_client'] ?? 'Client'); ?></td>
                                        <td>€<?php echo number_format($order['montant_total'], 2); ?></td>
                                        <td>
                                            <?php 
                                            $status_badges = [
                                                'en_attente' => '<span class="order-status status-pending">⏳</span>',
                                                'en_preparation' => '<span class="order-status status-preparing">🔥</span>',
                                                'prete' => '<span class="order-status status-ready">✅</span>',
                                                'livree' => '<span class="order-status status-delivered">🚚</span>',
                                                'annulee' => '<span class="order-status status-cancelled">❌</span>'
                                            ];
                                            echo $status_badges[$order['statut']] ?? $order['statut'];
                                            ?>
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
                        <div class="compact-table">
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
                                        <td>€<?php echo number_format($plat['revenu'], 2); ?></td>
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
                        <div style="padding: 1rem 0;">
                            <div style="margin-bottom: 1rem;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem;">
                                    <span style="color: var(--text-secondary); font-size: 0.85rem;">En attente</span>
                                    <span style="color: var(--text-primary); font-weight: 500;"><?php echo $stats['pending_orders']; ?></span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo ($stats['pending_orders'] / max($stats['total_orders'], 1)) * 100; ?>%"></div>
                                </div>
                            </div>
                            
                            <div style="margin-bottom: 1rem;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem;">
                                    <span style="color: var(--text-secondary); font-size: 0.85rem;">En préparation</span>
                                    <span style="color: var(--text-primary); font-weight: 500;"><?php echo $stats['preparing_orders']; ?></span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo ($stats['preparing_orders'] / max($stats['total_orders'], 1)) * 100; ?>%"></div>
                                </div>
                            </div>
                            
                            <div style="margin-bottom: 1rem;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem;">
                                    <span style="color: var(--text-secondary); font-size: 0.85rem;">Prêtes</span>
                                    <span style="color: var(--text-primary); font-weight: 500;"><?php echo $stats['ready_orders']; ?></span>
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
                <h3 style="color: var(--text-primary); font-size: 1.25rem; font-weight: 600; margin-bottom: 1.5rem;">Actions rapides</h3>
                
                <div class="quick-actions">
                    <a href="commandes.php" class="action-btn">
                        <div class="action-icon">📋</div>
                        <div class="action-content">
                            <h4>Gérer les commandes</h4>
                            <p>Voir et gérer toutes les commandes</p>
                        </div>
                    </a>
                    
                    <a href="plats.php" class="action-btn">
                        <div class="action-icon">🍽️</div>
                        <div class="action-content">
                            <h4>Modifier le menu</h4>
                            <p>Ajouter ou modifier des plats</p>
                        </div>
                    </a>
                    
                    <a href="utilisateurs.php" class="action-btn">
                        <div class="action-icon">👥</div>
                        <div class="action-content">
                            <h4>Gérer le personnel</h4>
                            <p>Ajouter ou modifier des employés</p>
                        </div>
                    </a>
                    
                    <a href="#" class="action-btn">
                        <div class="action-icon">📊</div>
                        <div class="action-content">
                            <h4>Générer un rapport</h4>
                            <p>Exporter les données du mois</p>
                        </div>
                    </a>
                </div>
            </div>
        </main>
    </div>

    <script src="../assets/js/bootstrap.bundle.min.js"></script>
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