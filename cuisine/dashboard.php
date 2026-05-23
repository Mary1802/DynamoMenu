<?php
// Dashboard Cuisinier - Gestion des commandes en cuisine
session_start();

// Vérifier l'authentification
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'cuisinier') {
    header('Location: ../index.php');
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
        $stmt = $pdo->prepare("UPDATE commandes SET statut = 'en_preparation' WHERE id = ?");
        $stmt->execute([$commande_id]);
    } elseif ($action === 'termine') {
        $stmt = $pdo->prepare("UPDATE commandes SET statut = 'prete', date_fin = NOW() WHERE id = ?");
        $stmt->execute([$commande_id]);
    }
}

// Récupérer les statistiques
$stats = [
    'en_attente' => $pdo->query("SELECT COUNT(*) FROM commandes WHERE statut = 'en_attente'")->fetchColumn(),
    'en_preparation' => $pdo->query("SELECT COUNT(*) FROM commandes WHERE statut = 'en_preparation'")->fetchColumn(),
    'prete' => $pdo->query("SELECT COUNT(*) FROM commandes WHERE statut = 'prete'")->fetchColumn(),
];

// Récupérer les commandes en attente et en préparation
$stmt = $pdo->prepare("
    SELECT c.*, 
           COUNT(d.id) as nombre_plats,
           GROUP_CONCAT(CONCAT(d.nom_plat, ' (x', d.quantite, ')') SEPARATOR ', ') as details_plats
    FROM commandes c
    LEFT JOIN details_commandes d ON c.id = d.commande_id
    WHERE c.statut IN ('en_attente', 'en_preparation')
    GROUP BY c.id
    ORDER BY c.statut DESC, c.date_creation ASC
");
$stmt->execute();
$commandes_actives = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les commandes terminées (dernières 10)
$stmt = $pdo->prepare("
    SELECT c.*, 
           COUNT(d.id) as nombre_plats,
           GROUP_CONCAT(CONCAT(d.nom_plat, ' (x', d.quantite, ')') SEPARATOR ', ') as details_plats
    FROM commandes c
    LEFT JOIN details_commandes d ON c.id = d.commande_id
    WHERE c.statut = 'prete'
    GROUP BY c.id
    ORDER BY c.date_fin DESC
    LIMIT 10
");
$stmt->execute();
$commandes_terminees = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cuisinier - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        :root {
            --accent-color: #ff6f1f;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --bg-panel: #0b0b0c;
            --panel-2: #0f0f10;
        }
        
        body {
            background: linear-gradient(180deg, #070707, #0b0b0d);
            color: #e6e6e6;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 240px;
            background: var(--bg-panel);
            min-height: 100vh;
            box-shadow: 6px 0 18px rgba(0, 0, 0, 0.6);
            position: sticky;
            top: 0;
        }
        
        .sidebar .brand {
            text-align: center;
            padding: 18px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            margin-bottom: 24px;
        }
        
        .sidebar .brand .logo {
            width: 56px;
            height: 56px;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--accent-color), #ff8a3d);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
            margin: 0 auto 8px;
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.65);
            padding: 12px 14px;
            border-radius: 10px;
            margin-bottom: 8px;
            transition: all 0.3s ease;
        }
        
        .sidebar .nav-link:hover {
            background: rgba(255, 255, 255, 0.02);
            color: #fff;
        }
        
        .sidebar .nav-link.active {
            background: linear-gradient(90deg, rgba(255, 111, 31, 0.15), rgba(255, 111, 31, 0.05));
            color: var(--accent-color);
            box-shadow: inset 0 0 0 1px rgba(255, 111, 31, 0.1);
        }
        
        .card-dark {
            background: linear-gradient(180deg, #0f0f10, #0e0e10);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.03);
            color: #eaeaea;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.5);
            transition: all 0.3s ease;
        }
        
        .card-dark:hover {
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.6);
        }
        
        .stat-card {
            display: flex;
            align-items: center;
            padding: 20px;
        }
        
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.05);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 16px;
            font-size: 24px;
        }
        
        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: #fff;
            line-height: 1;
        }
        
        .stat-label {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.7);
            margin-top: 4px;
        }
        
        .order-card {
            background: linear-gradient(180deg, #0f0f10, #0e0e10);
            border: 1px solid rgba(255, 255, 255, 0.03);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 12px;
            transition: all 0.3s ease;
        }
        
        .order-card:hover {
            border-color: rgba(255, 111, 31, 0.3);
            box-shadow: 0 4px 16px rgba(255, 111, 31, 0.1);
        }
        
        .order-card.en-attente {
            border-left: 4px solid var(--danger-color);
        }
        
        .order-card.en-preparation {
            border-left: 4px solid var(--warning-color);
        }
        
        .order-card.prete {
            border-left: 4px solid var(--success-color);
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        
        .order-id {
            font-weight: 600;
            color: #fff;
            font-size: 1.1rem;
        }
        
        .order-status {
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .status-attente {
            background: rgba(220, 53, 69, 0.2);
            color: #ff6b6b;
        }
        
        .status-preparation {
            background: rgba(255, 193, 7, 0.2);
            color: #ffc107;
        }
        
        .status-prete {
            background: rgba(40, 167, 69, 0.2);
            color: #28a745;
        }
        
        .order-details {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.95rem;
            margin-bottom: 12px;
        }
        
        .order-items {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 12px;
        }
        
        .order-item {
            padding: 6px 0;
            color: rgba(255, 255, 255, 0.85);
            font-size: 0.95rem;
        }
        
        .order-actions {
            display: flex;
            gap: 8px;
        }
        
        .btn-sm-action {
            padding: 6px 12px;
            font-size: 0.85rem;
            border-radius: 6px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #fff;
            cursor: pointer;
            transition: all 0.3s ease;
            flex: 1;
        }
        
        .btn-en-cours {
            background: linear-gradient(135deg, var(--warning-color), #ffb300);
            border: 0;
        }
        
        .btn-en-cours:hover {
            background: linear-gradient(135deg, #e0a800, #ffa500);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 193, 7, 0.3);
        }
        
        .btn-termine {
            background: linear-gradient(135deg, var(--success-color), #20c997);
            border: 0;
        }
        
        .btn-termine:hover {
            background: linear-gradient(135deg, #218838, #17a2b8);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
        }
        
        .section-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #fff;
            margin: 24px 0 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .section-title-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: rgba(255, 111, 31, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--accent-color);
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: rgba(255, 255, 255, 0.5);
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 16px;
            opacity: 0.5;
        }
        
        .header-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
        }
        
        .header-title h2 {
            font-size: 1.8rem;
            font-weight: 700;
            color: #fff;
            margin: 0;
        }
        
        .header-title .subtitle {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.6);
            margin-top: 4px;
        }
        
        .header-actions {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        
        .refresh-btn {
            padding: 8px 16px;
            background: rgba(255, 111, 31, 0.1);
            border: 1px solid rgba(255, 111, 31, 0.3);
            border-radius: 8px;
            color: var(--accent-color);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .refresh-btn:hover {
            background: rgba(255, 111, 31, 0.2);
            transform: rotate(180deg);
        }
    </style>
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <aside class="sidebar p-3 d-flex flex-column">
            <div class="brand">
                <div class="logo">👨‍🍳</div>
                <div class="fw-bold">DynamoMenu</div>
                <div style="font-size: 0.75rem; color: rgba(255,255,255,0.5);">Cuisine</div>
            </div>
            
            <nav class="nav flex-column mb-4">
                <a class="nav-link active mb-1" href="dashboard.php">
                    <i class="bi bi-house-fill me-2"></i> Dashboard
                </a>
                <a class="nav-link mb-1" href="commandes.php">
                    <i class="bi bi-list-check me-2"></i> Commandes
                </a>
                <a class="nav-link mb-1" href="#">
                    <i class="bi bi-gear me-2"></i> Paramètres
                </a>
            </nav>
            
            <div class="mt-auto small" style="color: rgba(255,255,255,0.5);">
                <div style="font-size: 0.85rem; margin-bottom: 12px;">
                    <strong><?php echo htmlspecialchars($_SESSION['nom'] ?? 'Cuisinier'); ?></strong><br>
                    Aujourd'hui - <?php echo strftime('%d %B', time()); ?>
                </div>
                <a href="../utils/logout.php" class="text-decoration-none" style="color: rgba(255,111,31,0.8); font-size: 0.9rem;">
                    <i class="bi bi-box-arrow-left me-2"></i> Déconnexion
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-grow-1 p-4">
            <!-- Header -->
            <div class="header-top">
                <div class="header-title">
                    <h2>🍳 Dashboard Cuisine</h2>
                    <div class="subtitle">Gérez les commandes en temps réel</div>
                </div>
                <div class="header-actions">
                    <button class="refresh-btn" onclick="location.reload()">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>
                </div>
            </div>

            <!-- Statistiques -->
            <div class="row g-3 mb-4">
                <div class="col-sm-6 col-lg-3">
                    <div class="card card-dark stat-card">
                        <div class="stat-icon" style="color: var(--danger-color);">
                            <i class="bi bi-clock"></i>
                        </div>
                        <div>
                            <div class="stat-value"><?php echo $stats['en_attente']; ?></div>
                            <div class="stat-label">En attente</div>
                        </div>
                    </div>
                </div>
                
                <div class="col-sm-6 col-lg-3">
                    <div class="card card-dark stat-card">
                        <div class="stat-icon" style="color: var(--warning-color);">
                            <i class="bi bi-fire"></i>
                        </div>
                        <div>
                            <div class="stat-value"><?php echo $stats['en_preparation']; ?></div>
                            <div class="stat-label">En préparation</div>
                        </div>
                    </div>
                </div>
                
                <div class="col-sm-6 col-lg-3">
                    <div class="card card-dark stat-card">
                        <div class="stat-icon" style="color: var(--success-color);">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <div>
                            <div class="stat-value"><?php echo $stats['prete']; ?></div>
                            <div class="stat-label">Prête à servir</div>
                        </div>
                    </div>
                </div>
                
                <div class="col-sm-6 col-lg-3">
                    <div class="card card-dark stat-card">
                        <div class="stat-icon" style="color: var(--accent-color);">
                            <i class="bi bi-graph-up"></i>
                        </div>
                        <div>
                            <div class="stat-value"><?php echo $stats['en_attente'] + $stats['en_preparation']; ?></div>
                            <div class="stat-label">Total actif</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Commandes Actives -->
            <div class="section-title">
                <div class="section-title-icon"><i class="bi bi-list-task"></i></div>
                Commandes à préparer
            </div>
            
            <div class="row">
                <div class="col-lg-8">
                    <?php if (empty($commandes_actives)): ?>
                        <div class="card card-dark">
                            <div class="empty-state">
                                <i class="bi bi-inbox"></i>
                                <p>Aucune commande en attente</p>
                                <small style="color: rgba(255,255,255,0.4);">Vous êtes à jour ! 🎉</small>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($commandes_actives as $commande): ?>
                            <div class="order-card <?php echo 'en-' . str_replace('_', '-', $commande['statut']); ?>">
                                <div class="order-header">
                                    <span class="order-id">#<?php echo str_pad($commande['id'], 5, '0', STR_PAD_LEFT); ?></span>
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
                                    <i class="bi bi-clock"></i> 
                                    <?php 
                                    $time = strtotime($commande['date_creation']);
                                    $elapsed = time() - $time;
                                    $mins = floor($elapsed / 60);
                                    echo $mins > 0 ? $mins . ' min' : 'À l\'instant';
                                    ?>
                                    | <i class="bi bi-box"></i> <?php echo $commande['nombre_plats']; ?> plat(s)
                                </div>
                                
                                <div class="order-items">
                                    <?php 
                                    $items = explode(', ', $commande['details_plats']);
                                    foreach ($items as $item): 
                                    ?>
                                        <div class="order-item">
                                            • <?php echo htmlspecialchars($item); ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="order-actions">
                                    <?php if ($commande['statut'] === 'en_attente'): ?>
                                        <form method="POST" style="width: 100%;">
                                            <input type="hidden" name="action" value="en_cours">
                                            <input type="hidden" name="commande_id" value="<?php echo $commande['id']; ?>">
                                            <button type="submit" class="btn-sm-action btn-en-cours">
                                                <i class="bi bi-fire"></i> Commencer
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <?php if ($commande['statut'] === 'en_preparation'): ?>
                                        <form method="POST" style="width: 100%;">
                                            <input type="hidden" name="action" value="termine">
                                            <input type="hidden" name="commande_id" value="<?php echo $commande['id']; ?>">
                                            <button type="submit" class="btn-sm-action btn-termine">
                                                <i class="bi bi-check-circle"></i> Terminé
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Sidebar Commandes Prêtes -->
                <div class="col-lg-4">
                    <div class="card card-dark p-3">
                        <h5 class="mb-3">
                            <i class="bi bi-check-all"></i> À servir
                        </h5>
                        
                        <?php if (empty($commandes_terminees)): ?>
                            <div class="empty-state" style="padding: 30px 15px;">
                                <i class="bi bi-cup"></i>
                                <p style="font-size: 0.9rem; margin: 8px 0 0;">Aucune commande prête</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($commandes_terminees as $cmd): ?>
                                <div class="order-card prete" style="margin-bottom: 8px;">
                                    <div class="order-header">
                                        <span class="order-id" style="font-size: 1rem;">#<?php echo str_pad($cmd['id'], 5, '0', STR_PAD_LEFT); ?></span>
                                    </div>
                                    <div class="order-status status-prete">✓ Prêt</div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-refresh toutes les 10 secondes
        setTimeout(() => {
            location.reload();
        }, 10000);
    </script>
</body>
</html>
