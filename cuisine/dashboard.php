<?php
// Dashboard Cuisinier - Gestion des commandes en cuisine
session_start();

// Vérifier l'authentification
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'cuisinier') {
    // rediriger vers la page d'accueil client
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

// Traiter les actions (mise à jour du statut)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $commande_id = $_POST['commande_id'] ?? '';

    if ($action === 'en_cours') {
        $stmt = $pdo->prepare("UPDATE commande SET statut = 'en_preparation' WHERE num_commande = ?");
        $stmt->execute([$commande_id]);
    } elseif ($action === 'termine') {
        $stmt = $pdo->prepare("UPDATE commande SET statut = 'prete' WHERE num_commande = ?");
        $stmt->execute([$commande_id]);
    }

    // Post/Redirect/Get pour éviter la resoumission de formulaire
    header('Location: dashboard.php');
    exit;
}

// Récupérer les statistiques
$stats = [
    'en_attente' => $pdo->query("SELECT COUNT(*) FROM commande WHERE statut = 'en_attente'")->fetchColumn(),
    'en_preparation' => $pdo->query("SELECT COUNT(*) FROM commande WHERE statut = 'en_preparation'")->fetchColumn(),
    'prete' => $pdo->query("SELECT COUNT(*) FROM commande WHERE statut = 'prete'")->fetchColumn(),
];

// Récupérer les commandes en attente et en préparation
$stmt = $pdo->prepare("
    SELECT c.*, 
           COUNT(d.id_detail) as nombre_items,
           GROUP_CONCAT(CONCAT(COALESCE(p.nom_plat, b.nom_boisson), ' (x', d.quantite, ')') SEPARATOR ', ') as details_plats
    FROM commande c
    LEFT JOIN contient d ON c.num_commande = d.num_commande
    LEFT JOIN plat p ON d.id_plat = p.id_plat
    LEFT JOIN boisson b ON d.id_boisson = b.id_boisson
    WHERE c.statut IN ('en_attente', 'en_preparation')
    GROUP BY c.num_commande
    ORDER BY c.statut DESC, c.date_commande ASC
");
$stmt->execute();
$commandes_actives = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les commandes terminées (dernières 10)
$stmt = $pdo->prepare("
    SELECT c.*, 
           COUNT(d.id_detail) as nombre_items,
           GROUP_CONCAT(CONCAT(COALESCE(p.nom_plat, b.nom_boisson), ' (x', d.quantite, ')') SEPARATOR ', ') as details_plats
    FROM commande c
    LEFT JOIN contient d ON c.num_commande = d.num_commande
    LEFT JOIN plat p ON d.id_plat = p.id_plat
    LEFT JOIN boisson b ON d.id_boisson = b.id_boisson
    WHERE c.statut = 'prete'
    GROUP BY c.num_commande
    ORDER BY c.date_commande DESC
    LIMIT 10
");
$stmt->execute();
$commandes_terminees = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cuisinier - Dashboard</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboards.css">
    <style>
        /* Styles spécifiques au dashboard cuisinier */
        .cuisine-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .order-timeline {
            position: relative;
            padding-left: 1.5rem;
        }
        
        .order-timeline::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 2px;
            background: linear-gradient(to bottom, var(--primary-color), transparent);
        }
        
        .order-time {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-bottom: 0.25rem;
        }
        
        .kitchen-timer {
            background: rgba(255, 111, 31, 0.1);
            border: 1px solid rgba(255, 111, 31, 0.2);
            border-radius: 8px;
            padding: 0.5rem 1rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            color: var(--primary-color);
        }
        
        .priority-badge {
            background: rgba(220, 53, 69, 0.15);
            color: var(--danger-color);
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 500;
        }
    </style>
</head>
<body class="dashboard-body">
    <div class="d-flex">
        <!-- Sidebar -->
        <aside class="dashboard-sidebar d-flex flex-column">
            <div class="sidebar-brand">
                <div class="brand-logo">🍳</div>
                <div class="brand-title">DynamoMenu</div>
                <div class="brand-subtitle">Cuisine</div>
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
                    <a class="nav-link" href="#">
                        <span class="nav-icon">⚙️</span>
                        <span>Paramètres</span>
                    </a>
                </div>
            </nav>
            
            <div class="sidebar-footer">
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo substr($_SESSION['nom'] ?? 'C', 0, 1); ?>
                    </div>
                    <div class="user-details">
                        <div class="user-name"><?php echo htmlspecialchars($_SESSION['nom'] ?? 'Cuisinier'); ?></div>
                        <div class="user-role">Cuisinier</div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="dashboard-main">
            <!-- Header -->
            <header class="dashboard-header">
                <div class="header-title">
                    <h1>Bonjour, Cuisinier 👨‍🍳</h1>
                    <p>Gérez les commandes en temps réel</p>
                </div>
                
                <div class="header-actions">
                    <div class="search-box">
                        <input type="text" class="search-input" placeholder="Rechercher une commande...">
                        <span class="search-icon">🔍</span>
                    </div>
                    
                    <a href="#" class="notification-btn">
                        <span>🔔</span>
                        <span class="notification-badge">3</span>
                    </a>
                </div>
            </header>

            <!-- Stats Cards -->
            <div class="cuisine-stats">
                <div class="dashboard-card stat-card">
                    <div class="stat-icon warning">⏳</div>
                    <div class="stat-value"><?php echo $stats['en_attente']; ?></div>
                    <div class="stat-label">En attente</div>
                    <div class="stat-change positive">+2 aujourd'hui</div>
                </div>
                
                <div class="dashboard-card stat-card">
                    <div class="stat-icon primary">🔥</div>
                    <div class="stat-value"><?php echo $stats['en_preparation']; ?></div>
                    <div class="stat-label">En préparation</div>
                    <div class="stat-change positive">En cours</div>
                </div>
                
                <div class="dashboard-card stat-card">
                    <div class="stat-icon success">✅</div>
                    <div class="stat-value"><?php echo $stats['prete']; ?></div>
                    <div class="stat-label">Prêtes à servir</div>
                    <div class="stat-change positive">+5 cette heure</div>
                </div>
                
                <div class="dashboard-card stat-card">
                    <div class="stat-icon info">📊</div>
                    <div class="stat-value"><?php echo $stats['en_attente'] + $stats['en_preparation']; ?></div>
                    <div class="stat-label">Total actif</div>
                    <div class="stat-change positive">Chargement optimal</div>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div class="row g-4">
                <div class="col-lg-8">
                    <!-- Commandes en cours -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <div>
                                <h3 class="card-title">Commandes en cours</h3>
                                <p class="card-subtitle">Vue en temps réel</p>
                            </div>
                            <div class="kitchen-timer">
                                <span>⏱️</span>
                                <span>Temps moyen: 15min</span>
                            </div>
                        </div>
                        
                        <div class="order-timeline">
                            <?php if (empty($commandes_actives)): ?>
                                <div class="empty-state">
                                    <div class="empty-icon">📭</div>
                                    <h4>Aucune commande en attente</h4>
                                    <p>Toutes les commandes sont traitées !</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($commandes_actives as $commande): ?>
                                    <div class="order-card">
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
                                            <span class="order-status status-<?php echo str_replace('_', '-', $commande['statut']); ?>">
                                                <?php 
                                                $statuts = [
                                                    'en_attente' => '⏳ En attente',
                                                    'en_preparation' => '🔥 En préparation'
                                                ];
                                                echo $statuts[$commande['statut']] ?? $commande['statut'];
                                                ?>
                                            </span>
                                        </div>
                                        
                                        <div class="order-details">
                                            <span>📦 <?php echo $commande['nombre_items']; ?> article(s)</span>
                                            <span>💰 <?php echo number_format($commande['montant_total'], 2); ?>€</span>
                                            <?php if ($commande['nombre_items'] > 3): ?>
                                                <span class="priority-badge">Priorité</span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="order-items">
                                            <?php $items = explode(', ', $commande['details_plats']); foreach ($items as $item): ?>
                                                <div class="order-item">• <?php echo htmlspecialchars($item); ?></div>
                                            <?php endforeach; ?>
                                        </div>
                                        
                                        <div class="order-actions">
                                            <?php if ($commande['statut'] === 'en_attente'): ?>
                                                <form method="POST" class="w-100">
                                                    <input type="hidden" name="action" value="en_cours">
                                                    <input type="hidden" name="commande_id" value="<?php echo $commande['num_commande']; ?>">
                                                    <button type="submit" class="btn-primary w-100">
                                                        <span>🔥</span>
                                                        <span>Commencer la préparation</span>
                                                    </button>
                                                </form>
                                            <?php elseif ($commande['statut'] === 'en_preparation'): ?>
                                                <form method="POST" class="w-100">
                                                    <input type="hidden" name="action" value="termine">
                                                    <input type="hidden" name="commande_id" value="<?php echo $commande['num_commande']; ?>">
                                                    <button type="submit" class="btn-primary w-100" style="background: linear-gradient(135deg, var(--success-color), #20c997);">
                                                        <span>✅</span>
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
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3 class="card-title">À servir</h3>
                            <a href="#" class="card-action">Voir tout</a>
                        </div>
                        
                        <?php if (empty($commandes_terminees)): ?>
                            <div class="empty-state">
                                <div class="empty-icon">🍽️</div>
                                <p>Aucune commande prête à servir</p>
                            </div>
                        <?php else: ?>
                            <div class="order-timeline">
                                <?php foreach ($commandes_terminees as $cmd): ?>
                                    <div class="order-card" style="margin-bottom: 0.75rem;">
                                        <div class="order-header">
                                            <div class="order-id">#<?php echo str_pad($cmd['num_commande'],5,'0',STR_PAD_LEFT); ?></div>
                                            <span class="order-status status-ready">✅ Prêt</span>
                                        </div>
                                        <div class="order-details">
                                            <span>📦 <?php echo $cmd['nombre_items']; ?> article(s)</span>
                                            <span>💰 <?php echo number_format($cmd['montant_total'], 2); ?>€</span>
                                        </div>
                                        <div class="order-actions">
                                            <button class="btn-outline w-100">
                                                <span>🔔</span>
                                                <span>Notifier le service</span>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Statistiques rapides -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3 class="card-title">Statistiques</h3>
                        </div>
                        <div class="order-details">
                            <div style="margin-bottom: 1rem;">
                                <div style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 0.25rem;">Temps moyen de préparation</div>
                                <div style="font-size: 1.5rem; font-weight: 600; color: var(--text-primary);">15:24</div>
                            </div>
                            <div style="margin-bottom: 1rem;">
                                <div style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 0.25rem;">Commandes aujourd'hui</div>
                                <div style="font-size: 1.5rem; font-weight: 600; color: var(--text-primary);">42</div>
                            </div>
                            <div>
                                <div style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 0.25rem;">Satisfaction clients</div>
                                <div style="font-size: 1.5rem; font-weight: 600; color: var(--text-primary);">4.8/5</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
