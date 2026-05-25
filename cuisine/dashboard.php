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
    <style>
        :root{ --accent-color: #f4c95a; --bg-panel:#0b0b0c; --panel-2:#0f0f10; }
        body { background: linear-gradient(180deg,#070707,#0b0b0d); color: #e6e6e6; }
        .admin-sidebar{ width:240px; background: var(--bg-panel); min-height:100vh; box-shadow: 6px 0 18px rgba(0,0,0,0.6); }
        .admin-sidebar .brand { text-align:center; padding:18px 0; }
        .admin-sidebar .nav-link{ color:rgba(255,255,255,0.65); padding:12px 14px; border-radius:10px; }
        .admin-sidebar .nav-link:hover{ background: rgba(255,255,255,0.02); color:#fff; }
        .admin-sidebar .nav-link.active{ background: linear-gradient(90deg, rgba(244,201,90,0.06), rgba(244,201,90,0.02)); color:var(--accent-color); box-shadow: inset 0 0 0 1px rgba(244,201,90,0.04); }
        .card-dark{ background: linear-gradient(180deg,#0f0f10,#0e0e10); border-radius:12px; border:1px solid rgba(255,255,255,0.03); color:#eaeaea; box-shadow: 0 6px 20px rgba(0,0,0,0.5); }
        .small-muted{ color:rgba(255,255,255,0.6); font-size:0.9rem; }
        .stat-value{ font-size:1.6rem; font-weight:700; color:#fff; }
        .orders-table td, .orders-table th{ border-top:0; }
        .chip{ background: rgba(255,255,255,0.03); padding:8px 12px; border-radius:999px; color:#fff; }
        .stat-card .icon-wrap{ width:44px;height:44px;border-radius:10px;background:rgba(255,255,255,0.03);display:inline-flex;align-items:center;justify-content:center;margin-right:12px }
        .stat-card .meta{ font-size:0.85rem;color:rgba(255,255,255,0.7) }
        .orders-table tbody tr:hover{ background: rgba(255,255,255,0.01); }
        .right-column .card-dark{ margin-bottom:16px }

        /* order card specifics */
        .order-card{ background: linear-gradient(180deg, #0f0f10, #0e0e10); border: 1px solid rgba(255,255,255,0.03); border-radius:12px; padding:16px; margin-bottom:12px }
        .order-card.en-attente{ border-left:4px solid #dc3545 }
        .order-card.en-preparation{ border-left:4px solid #ffc107 }
        .order-card.prete{ border-left:4px solid #28a745 }
        .order-header{ display:flex; justify-content:space-between; align-items:center; margin-bottom:12px }
        .order-id{ font-weight:600; color:#fff; font-size:1.1rem }
        .order-status{ padding:4px 12px; border-radius:999px; font-size:0.85rem; font-weight:500 }
        .order-details{ color: rgba(255,255,255,0.8); font-size:0.95rem; margin-bottom:12px }
        .order-items{ background: rgba(0,0,0,0.2); border-radius:8px; padding:12px; margin-bottom:12px }
        .order-item{ padding:6px 0; color:rgba(255,255,255,0.85); font-size:0.95rem }
        .order-actions{ display:flex; gap:8px }
        .btn-sm-action{ padding:6px 12px; font-size:0.85rem; border-radius:6px; border:1px solid rgba(255,255,255,0.1); color:#fff; cursor:pointer }
        .btn-en-cours{ background: linear-gradient(135deg,#ffc107,#ffb300); border:0 }
        .btn-termine{ background: linear-gradient(135deg,#28a745,#20c997); border:0 }
        .section-title{ font-size:1.3rem; font-weight:600; color:#fff; margin:24px 0 16px; display:flex; align-items:center; gap:8px }
        .section-title-icon{ width:32px; height:32px; border-radius:8px; background: rgba(244,201,90,0.15); display:flex; align-items:center; justify-content:center; color:var(--accent-color) }
    </style>
</head>
<body>
    <div class="d-flex">
        <aside class="admin-sidebar p-3 d-flex flex-column">
            <div class="mb-4 text-center">
                <div class="rounded-circle bg-warning d-inline-flex align-items-center justify-content-center" style="width:56px;height:56px">DM</div>
                <div class="mt-2 fw-bold">DynamoMenu</div>
            </div>
            <nav class="nav flex-column mb-4">
                <a class="nav-link active mb-1" href="dashboard.php">Dashboard</a>
                <a class="nav-link mb-1" href="commandes.php">Commandes</a>
                <a class="nav-link mb-1" href="#">Paramètres</a>
            </nav>
            <div class="mt-auto small-muted">Role: Cuisinier<br><strong><?php echo htmlspecialchars($_SESSION['nom'] ?? 'Cuisinier'); ?></strong></div>
        </aside>

        <main class="flex-grow-1 p-4">
            <header class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h2 class="mb-0">Welcome back, Cuisinier</h2>
                    <div class="small-muted">Gérez les commandes en temps réel</div>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <div class="chip">Search...</div>
                    <div class="position-relative">
                        <a class="btn btn-outline-light position-relative" href="#">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-bell" viewBox="0 0 16 16"><path d="M8 16a2 2 0 0 0 1.985-1.75H6.015A2 2 0 0 0 8 16z"/></svg>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning">3</span>
                        </a>
                    </div>
                    <img src="../assets/images/user.png" alt="user" style="width:40px;height:40px;border-radius:50%"/>
                </div>
            </header>

            <div class="mb-4 row g-3">
                <div class="col-sm-6 col-lg-3">
                    <div class="card card-dark p-3 stat-card d-flex align-items-center">
                        <div class="icon-wrap">
                            <svg width="20" height="20" fill="currentColor" style="color:var(--accent-color)" viewBox="0 0 16 16"><path d="M8 0a8 8 0 1 0 0 16A8 8 0 0 0 8 0z"/></svg>
                        </div>
                        <div>
                            <div class="meta small-muted">En attente</div>
                            <div class="stat-value"><?php echo $stats['en_attente']; ?></div>
                            <div class="small-muted">Surveillance</div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card card-dark p-3 stat-card d-flex align-items-center">
                        <div class="icon-wrap">
                            <svg width="20" height="20" fill="currentColor" style="color:var(--accent-color)" viewBox="0 0 16 16"><path d="M4 4h8v2H4z"/></svg>
                        </div>
                        <div>
                            <div class="meta small-muted">En préparation</div>
                            <div class="stat-value"><?php echo $stats['en_preparation']; ?></div>
                            <div class="small-muted">En cours</div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card card-dark p-3 stat-card d-flex align-items-center">
                        <div class="icon-wrap">
                            <svg width="20" height="20" fill="currentColor" style="color:var(--accent-color)" viewBox="0 0 16 16"><path d="M0 0h16v16H0z"/></svg>
                        </div>
                        <div>
                            <div class="meta small-muted">Prêtes</div>
                            <div class="stat-value"><?php echo $stats['prete']; ?></div>
                            <div class="small-muted">Prêtes à servir</div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card card-dark p-3 stat-card d-flex align-items-center">
                        <div class="icon-wrap">
                            <svg width="20" height="20" fill="currentColor" style="color:var(--accent-color)" viewBox="0 0 16 16"><path d="M8 1l2 4H6l2-4z"/></svg>
                        </div>
                        <div>
                            <div class="meta small-muted">Total actif</div>
                            <div class="stat-value"><?php echo $stats['en_attente'] + $stats['en_preparation']; ?></div>
                            <div class="small-muted">Chargement</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="card card-dark p-3 mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="fw-bold">Commandes en cours</div>
                            <div class="small-muted">Vue en temps réel</div>
                        </div>

                        <div>
                            <?php if (empty($commandes_actives)): ?>
                                <div class="empty-state p-4 text-center">
                                    <i class="bi bi-inbox" style="font-size:40px; opacity:0.5"></i>
                                    <p class="mt-3">Aucune commande en attente</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($commandes_actives as $commande): ?>
                                    <div class="order-card <?php echo 'en-' . str_replace('_', '-', $commande['statut']); ?>">
                                        <div class="order-header">
                                            <span class="order-id">#<?php echo str_pad($commande['num_commande'], 5, '0', STR_PAD_LEFT); ?></span>
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
                                            $time = strtotime($commande['date_commande']);
                                            $elapsed = time() - $time;
                                            $mins = floor($elapsed / 60);
                                            echo $mins > 0 ? $mins . ' min' : 'À l\'instant';
                                            ?>
                                            | <i class="bi bi-box"></i> <?php echo $commande['nombre_items']; ?> item(s)
                                        </div>
                                        <div class="order-items">
                                            <?php $items = explode(', ', $commande['details_plats']); foreach ($items as $item): ?>
                                                <div class="order-item">• <?php echo htmlspecialchars($item); ?></div>
                                            <?php endforeach; ?>
                                        </div>
                                        <div class="order-actions">
                                            <?php if ($commande['statut'] === 'en_attente'): ?>
                                                <form method="POST" style="width:100%">
                                                    <input type="hidden" name="action" value="en_cours">
                                                    <input type="hidden" name="commande_id" value="<?php echo $commande['num_commande']; ?>">
                                                    <button type="submit" class="btn-sm-action btn-en-cours"><i class="bi bi-fire"></i> Commencer</button>
                                                </form>
                                            <?php elseif ($commande['statut'] === 'en_preparation'): ?>
                                                <form method="POST" style="width:100%">
                                                    <input type="hidden" name="action" value="termine">
                                                    <input type="hidden" name="commande_id" value="<?php echo $commande['num_commande']; ?>">
                                                    <button type="submit" class="btn-sm-action btn-termine"><i class="bi bi-check-circle"></i> Terminé</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 right-column">
                    <div class="card card-dark p-3 mb-3">
                        <div class="fw-bold mb-2">À servir</div>
                        <?php if (empty($commandes_terminees)): ?>
                            <div class="empty-state p-3 text-center">Aucune commande prête</div>
                        <?php else: ?>
                            <?php foreach ($commandes_terminees as $cmd): ?>
                                <div class="card card-dark p-2 mb-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>#<?php echo str_pad($cmd['num_commande'],5,'0',STR_PAD_LEFT); ?></div>
                                        <div class="badge bg-success text-dark">Prêt</div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
