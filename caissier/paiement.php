<?php
session_start();

// Vérifier l'authentification
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'caissier') {
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

// Traiter le paiement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payer_commande'])) {
    $commande_id = $_POST['commande_id'];
    $mode_paiement = $_POST['mode_paiement'];
    $montant_paye = $_POST['montant_paye'];
    
    // Commencer une transaction
    $pdo->beginTransaction();
    
    try {
        // Mettre à jour le statut de la commande
        $stmt = $pdo->prepare("UPDATE commande SET statut = 'livree' WHERE num_commande = ?");
        $stmt->execute([$commande_id]);
        
        // Créer la facture
        $stmt = $pdo->prepare("INSERT INTO facture (num_commande, total_paye, mode_paiement) VALUES (?, ?, ?)");
        $stmt->execute([$commande_id, $montant_paye, $mode_paiement]);
        
        // Mettre à jour les demandes de paiement
        $stmt = $pdo->prepare("UPDATE demande_paiement SET statut = 'traitee', date_traitement = NOW() WHERE num_commande = ? AND statut = 'en_attente'");
        $stmt->execute([$commande_id]);
        
        // Valider la transaction
        $pdo->commit();
        
        // Rediriger pour éviter la resoumission
        header('Location: paiement.php?success=1&commande=' . $commande_id);
        exit;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Erreur lors du traitement du paiement: " . $e->getMessage();
    }
}

// Traiter l'annulation d'une demande de paiement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['annuler_demande'])) {
    $demande_id = $_POST['demande_id'];
    
    $stmt = $pdo->prepare("UPDATE demande_paiement SET statut = 'annulee', date_traitement = NOW() WHERE id_demande = ?");
    $stmt->execute([$demande_id]);
    
    header('Location: paiement.php');
    exit;
}

// Récupérer les commandes prêtes à payer (statut = 'prete')
$stmt = $pdo->prepare("
    SELECT 
        c.num_commande,
        c.date_commande,
        c.montant_total,
        t.num_table,
        cl.nom_client,
        COUNT(d.id_detail) as nombre_items
    FROM commande c
    LEFT JOIN table_restaurant t ON c.num_table = t.num_table
    LEFT JOIN client cl ON c.id_client = cl.id_client
    LEFT JOIN contient d ON c.num_commande = d.num_commande
    WHERE c.statut = 'prete'
    GROUP BY c.num_commande
    ORDER BY c.date_commande ASC
");
$stmt->execute();
$commandes_a_payer = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les détails d'une commande spécifique (pour le modal)
$commande_details = null;
if (isset($_GET['voir_commande'])) {
    $commande_id = $_GET['voir_commande'];
    $stmt = $pdo->prepare("
        SELECT 
            c.*,
            t.num_table,
            cl.nom_client,
            GROUP_CONCAT(
                CONCAT(
                    COALESCE(p.nom_plat, b.nom_boisson),
                    ' (x', d.quantite, ') - ',
                    d.prix, '€ = ',
                    d.sous_total, '€'
                ) SEPARATOR '||'
            ) as details_items
        FROM commande c
        LEFT JOIN table_restaurant t ON c.num_table = t.num_table
        LEFT JOIN client cl ON c.id_client = cl.id_client
        LEFT JOIN contient d ON c.num_commande = d.num_commande
        LEFT JOIN plat p ON d.id_plat = p.id_plat
        LEFT JOIN boisson b ON d.id_boisson = b.id_boisson
        WHERE c.num_commande = ?
        GROUP BY c.num_commande
    ");
    $stmt->execute([$commande_id]);
    $commande_details = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Récupérer les paiements récents
$stmt = $pdo->prepare("
    SELECT 
        f.*, 
        c.montant_total, 
        t.num_table,
        cl.nom_client,
        cl.prenom_client
    FROM facture f
    JOIN commande c ON f.num_commande = c.num_commande
    LEFT JOIN table_restaurant t ON c.num_table = t.num_table
    LEFT JOIN client cl ON c.id_client = cl.id_client
    ORDER BY f.date_facture DESC
    LIMIT 5
");
$stmt->execute();
$paiements_recents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les demandes de paiement en attente
$stmt = $pdo->prepare("
    SELECT 
        d.*,
        c.montant_total,
        c.date_commande,
        t.num_table,
        cl.nom_client,
        cl.prenom_client,
        cl.telephone_client
    FROM demande_paiement d
    JOIN commande c ON d.num_commande = c.num_commande
    LEFT JOIN table_restaurant t ON c.num_table = t.num_table
    LEFT JOIN client cl ON c.id_client = cl.id_client
    WHERE d.statut = 'en_attente'
    ORDER BY d.date_demande ASC
");
$stmt->execute();
$demandes_paiement = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Statistiques du jour
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_paiements,
        SUM(total_paye) as total_ca,
        AVG(total_paye) as moyenne_paiement,
        MIN(date_facture) as premier_paiement,
        MAX(date_facture) as dernier_paiement
    FROM facture 
    WHERE DATE(date_facture) = CURDATE()
");
$stmt->execute();
$stats_jour = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Caissier - Paiement des Commandes</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboards.css">
    <style>
        /* Styles spécifiques au dashboard caissier */
        .dashboard-container {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }
        
        .dashboard-main {
            flex: 1;
            padding: 1.5rem;
            overflow-y: auto;
            width: 100%;
        }
        
        .caissier-container {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            width: 100%;
        }
        
        .stats-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        @media (max-width: 1200px) {
            .stats-row {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .stats-row {
                grid-template-columns: 1fr;
            }
        }
        
        .stat-box {
            background: linear-gradient(135deg, var(--panel-bg) 0%, #0e0e0f 100%);
            border: 1px solid var(--panel-border);
            border-radius: 12px;
            padding: 1.25rem;
            text-align: center;
        }
        
        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0.5rem 0;
        }
        
        .stat-label {
            font-size: 0.85rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .main-content {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
            width: 100%;
        }
        
        @media (min-width: 1200px) {
            .main-content {
                grid-template-columns: 2fr 1fr;
            }
        }
        
        .commandes-section {
            background: linear-gradient(135deg, var(--panel-bg) 0%, #0e0e0f 100%);
            border: 1px solid var(--panel-border);
            border-radius: 16px;
            padding: 1.5rem;
        }
        
        .paiements-section {
            background: linear-gradient(135deg, var(--panel-bg) 0%, #0e0e0f 100%);
            border: 1px solid var(--panel-border);
            border-radius: 16px;
            padding: 1.5rem;
        }
        
        .section-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--panel-border);
        }
        
        .commande-item {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--panel-border);
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            transition: all 0.2s ease;
            cursor: pointer;
        }
        
        .commande-item:hover {
            border-color: var(--primary-color);
            background: rgba(255, 111, 31, 0.05);
        }
        
        .commande-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
        }
        
        .commande-id {
            font-weight: 600;
            color: var(--text-primary);
            font-size: 1.1rem;
        }
        
        .commande-montant {
            font-weight: 700;
            color: var(--primary-color);
            font-size: 1.2rem;
        }
        
        .commande-details {
            display: flex;
            justify-content: space-between;
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-bottom: 0.75rem;
        }
        
        .commande-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-details {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--panel-border);
            border-radius: 6px;
            padding: 0.5rem 1rem;
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.85rem;
            transition: all 0.2s ease;
            flex: 1;
            text-align: center;
        }
        
        .btn-details:hover {
            background: rgba(255, 111, 31, 0.1);
            border-color: var(--primary-color);
            color: var(--text-primary);
        }
        
        .btn-payer {
            background: linear-gradient(135deg, var(--success-color), #20c997);
            border: none;
            border-radius: 6px;
            padding: 0.5rem 1rem;
            color: white;
            font-weight: 500;
            font-size: 0.85rem;
            transition: all 0.2s ease;
            flex: 1;
            text-align: center;
            cursor: pointer;
        }
        
        .btn-payer:hover {
            background: linear-gradient(135deg, #20c997, #1ba87e);
            transform: translateY(-1px);
        }
        
        .paiement-item {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--panel-border);
            border-radius: 8px;
            padding: 0.75rem;
            margin-bottom: 0.75rem;
        }
        
        .paiement-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .paiement-id {
            font-weight: 500;
            color: var(--text-primary);
            font-size: 0.9rem;
        }
        
        .paiement-montant {
            font-weight: 600;
            color: var(--success-color);
        }
        
        .paiement-details {
            display: flex;
            justify-content: space-between;
            color: var(--text-secondary);
            font-size: 0.8rem;
        }
        
        .mode-badge {
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .mode-carte { background: rgba(33, 150, 243, 0.15); color: #2196f3; }
        .mode-especes { background: rgba(76, 175, 80, 0.15); color: #4caf50; }
        .mode-mobile { background: rgba(156, 39, 176, 0.15); color: #9c27b0; }
        
        .empty-state {
            text-align: center;
            padding: 2rem;
            color: var(--text-muted);
        }
        
        .empty-icon {
            font-size: 2.5rem;
            opacity: 0.3;
            margin-bottom: 1rem;
        }
        
        /* Modal styles */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: linear-gradient(135deg, var(--panel-bg) 0%, #0e0e0f 100%);
            border: 1px solid var(--panel-border);
            border-radius: 16px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--panel-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .modal-close {
            background: none;
            border: none;
            color: var(--text-muted);
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
        }
        
        .modal-close:hover {
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-primary);
        }
        
        .modal-body {
            padding: 1.5rem;
        }
        
        .commande-info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
        }
        
        .info-label {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-bottom: 0.25rem;
        }
        
        .info-value {
            font-size: 1rem;
            color: var(--text-primary);
            font-weight: 500;
        }
        
        .items-list {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            max-height: 200px;
            overflow-y: auto;
        }
        
        .item-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .item-row:last-child {
            border-bottom: none;
        }
        
        .item-name {
            color: var(--text-secondary);
            flex: 2;
        }
        
        .item-quantity {
            color: var(--text-muted);
            text-align: center;
            flex: 1;
        }
        
        .item-price {
            color: var(--text-primary);
            font-weight: 500;
            text-align: right;
            flex: 1;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 1rem 0;
            border-top: 2px solid var(--panel-border);
            font-weight: 600;
            color: var(--text-primary);
            font-size: 1.1rem;
        }
        
        .paiement-options {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }
        
        .paiement-option {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--panel-border);
            border-radius: 8px;
            padding: 1rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .paiement-option:hover,
        .paiement-option.active {
            background: rgba(255, 111, 31, 0.1);
            border-color: var(--primary-color);
        }
        
        .option-icon {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        .option-label {
            font-size: 0.85rem;
            color: var(--text-secondary);
        }
        
        .btn-confirm {
            background: linear-gradient(135deg, var(--success-color), #20c997);
            border: none;
            border-radius: 8px;
            padding: 1rem;
            color: white;
            font-weight: 600;
            font-size: 1rem;
            width: 100%;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .btn-confirm:hover {
            background: linear-gradient(135deg, #20c997, #1ba87e);
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(40, 167, 69, 0.3);
        }
        
        .success-message {
            background: rgba(40, 167, 69, 0.1);
            border: 1px solid rgba(40, 167, 69, 0.3);
            border-radius: 8px;
            padding: 1rem;
            color: var(--success-color);
            margin-bottom: 1.5rem;
            text-align: center;
        }
    </style>
</head>
<body class="dashboard-body">
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="dashboard-sidebar d-flex flex-column">
            <div class="sidebar-brand">
                <div class="brand-logo">💳</div>
                <div class="brand-title">DynamoMenu</div>
                <div class="brand-subtitle">Caisse</div>
            </div>
            
            <nav class="sidebar-nav">
                <div class="nav-item">
                    <a class="nav-link active" href="paiement.php">
                        <span class="nav-icon">💰</span>
                        <span>Paiements</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link" href="#">
                        <span class="nav-icon">📊</span>
                        <span>Rapports</span>
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
                        <div class="user-name"><?php echo htmlspecialchars($_SESSION['nom'] ?? 'Caissier'); ?></div>
                        <div class="user-role">Caissier</div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="dashboard-main flex-grow-1">
            <!-- Header -->
            <header class="dashboard-header">
                <div class="header-title">
                    <h1>Paiement des Commandes</h1>
                    <p>Gérez les paiements des commandes prêtes</p>
                </div>
                
                <div class="header-actions">
                    <div class="search-box">
                        <input type="text" class="search-input" placeholder="Rechercher une commande...">
                        <span class="search-icon">🔍</span>
                    </div>
                    
                    <a href="#" class="notification-btn">
                        <span>🔔</span>
                        <span class="notification-badge"><?php echo count($commandes_a_payer); ?></span>
                    </a>
                </div>
            </header>

            <!-- Message de succès -->
            <?php if (isset($_GET['success'])): ?>
            <div class="success-message">
                ✅ Paiement de la commande #<?php echo $_GET['commande'] ?? ''; ?> effectué avec succès !
            </div>
            <?php endif; ?>

            <!-- Statistiques -->
            <div class="stats-row">
                <div class="stat-box">
                    <div class="stat-value"><?php echo count($commandes_a_payer); ?></div>
                    <div class="stat-label">À Payer</div>
                </div>
                
                <div class="stat-box">
                    <div class="stat-value">€<?php echo number_format($stats_jour['total_ca'] ?? 0, 0, ',', ' '); ?></div>
                    <div class="stat-label">CA Aujourd'hui</div>
                </div>
                
                <div class="stat-box">
                    <div class="stat-value"><?php echo $stats_jour['total_paiements'] ?? 0; ?></div>
                    <div class="stat-label">Paiements</div>
                </div>
                
                <div class="stat-box">
                    <div class="stat-value">€<?php echo number_format($stats_jour['moyenne_paiement'] ?? 0, 2, ',', ' '); ?></div>
                    <div class="stat-label">Moyenne</div>
                </div>
            </div>

            <!-- Contenu principal -->
            <div class="main-content">
                <!-- Section Demandes de paiement -->
                <?php if (!empty($demandes_paiement)): ?>
                <div class="commandes-section" style="margin-bottom: 1.5rem;">
                    <div class="section-title">
                        Demandes de paiement
                        <span style="font-size: 0.9rem; color: var(--text-muted); margin-left: 0.5rem;">
                            (<?php echo count($demandes_paiement); ?> en attente)
                        </span>
                    </div>
                    
                    <div class="row g-3">
                        <?php foreach ($demandes_paiement as $demande): ?>
                        <div class="col-12">
                            <div class="commande-item" style="background: rgba(255, 193, 7, 0.1); border-color: #ffc107;">
                                <div class="commande-header">
                                    <div class="commande-id">
                                        Demande #<?php echo str_pad($demande['id_demande'], 4, '0', STR_PAD_LEFT); ?>
                                        <span style="font-size: 0.85rem; color: var(--text-muted);">
                                            (Commande #<?php echo str_pad($demande['num_commande'], 5, '0', STR_PAD_LEFT); ?>)
                                        </span>
                                    </div>
                                    <div class="commande-montant">€<?php echo number_format($demande['montant'], 2); ?></div>
                                </div>
                                
                                <div class="commande-details">
                                    <div>
                                        <span>Table <?php echo $demande['num_table'] ?? 'N/A'; ?></span>
                                        <span> • </span>
                                        <span><?php echo htmlspecialchars($demande['prenom_client'] . ' ' . $demande['nom_client']); ?></span>
                                    </div>
                                    <div>
                                        <?php 
                                        $mode_icons = [
                                            'carte' => '💳 Carte',
                                            'especes' => '💵 Espèces',
                                            'mobile' => '📱 Mobile'
                                        ];
                                        ?>
                                        <span><?php echo $mode_icons[$demande['mode_paiement']] ?? ucfirst($demande['mode_paiement']); ?></span>
                                        <span> • </span>
                                        <span><?php echo date('H:i', strtotime($demande['date_demande'])); ?></span>
                                    </div>
                                </div>
                                
                                <div class="commande-actions">
                                    <a href="?voir_commande=<?php echo $demande['num_commande']; ?>" class="btn-details" onclick="openModal(event, <?php echo $demande['num_commande']; ?>)">
                                        📋 Voir détails
                                    </a>
                                    <button class="btn-payer" onclick="openModal(event, <?php echo $demande['num_commande']; ?>)">
                                        💰 Traiter le paiement
                                    </button>
                                    <form method="POST" style="display: inline; flex: 1;">
                                        <input type="hidden" name="demande_id" value="<?php echo $demande['id_demande']; ?>">
                                        <button type="submit" name="annuler_demande" class="btn-details" style="background: rgba(220, 53, 69, 0.1); color: #dc3545; border-color: #dc3545;">
                                            ❌ Annuler
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Section Commandes à payer -->
                <div class="commandes-section">
                    <div class="section-title">
                        Commandes à payer
                        <span style="font-size: 0.9rem; color: var(--text-muted); margin-left: 0.5rem;">
                            (<?php echo count($commandes_a_payer); ?> en attente)
                        </span>
                    </div>
                    
                    <?php if (empty($commandes_a_payer)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">💰</div>
                        <h4>Aucune commande à payer</h4>
                        <p>Toutes les commandes ont été réglées</p>
                    </div>
                    <?php else: ?>
                    <div class="row g-3">
                        <?php foreach ($commandes_a_payer as $commande): ?>
                        <div class="col-12">
                            <div class="commande-item" data-commande-id="<?php echo $commande['num_commande']; ?>">
                                <div class="commande-header">
                                    <div class="commande-id">Commande #<?php echo str_pad($commande['num_commande'], 5, '0', STR_PAD_LEFT); ?></div>
                                    <div class="commande-montant">€<?php echo number_format($commande['montant_total'], 2); ?></div>
                                </div>
                                
                                <div class="commande-details">
                                    <div>
                                        <span>Table <?php echo $commande['num_table'] ?? 'N/A'; ?></span>
                                        <span> • </span>
                                        <span><?php echo $commande['nom_client'] ?? 'Client'; ?></span>
                                    </div>
                                    <div>
                                        <span><?php echo $commande['nombre_items']; ?> article(s)</span>
                                        <span> • </span>
                                        <span><?php echo date('H:i', strtotime($commande['date_commande'])); ?></span>
                                    </div>
                                </div>
                                
                                <div class="commande-actions">
                                    <a href="?voir_commande=<?php echo $commande['num_commande']; ?>" class="btn-details" onclick="openModal(event, <?php echo $commande['num_commande']; ?>)">
                                        📋 Voir détails
                                    </a>
                                    <button class="btn-payer" onclick="openModal(event, <?php echo $commande['num_commande']; ?>)">
                                        💰 Payer maintenant
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Section Paiements récents -->
                <div class="paiements-section">
                    <div class="section-title">Paiements récents</div>
                    
                    <?php if (empty($paiements_recents)): ?>
                    <div class="empty-state" style="padding: 1rem;">
                        <div class="empty-icon">📄</div>
                        <p>Aucun paiement récent</p>
                    </div>
                    <?php else: ?>
                    <div class="row g-2">
                        <?php foreach ($paiements_recents as $paiement): ?>
                        <div class="col-12">
                            <div class="paiement-item">
                                <div class="paiement-header">
                                    <div class="paiement-id">Facture #F-<?php echo str_pad($paiement['num_facture'], 4, '0', STR_PAD_LEFT); ?></div>
                                    <div class="paiement-montant">€<?php echo number_format($paiement['total_paye'], 2); ?></div>
                                </div>
                                
                                <div class="paiement-details">
                                    <div>
                                        <span>Table <?php echo $paiement['num_table'] ?? 'N/A'; ?></span>
                                        <span> • </span>
                                        <span><?php echo htmlspecialchars($paiement['prenom_client'] . ' ' . $paiement['nom_client']); ?></span>
                                    </div>
                                    <div>
                                        <?php 
                                        $badge_class = 'mode-' . $paiement['mode_paiement'];
                                        $badge_icon = '';
                                        switch($paiement['mode_paiement']) {
                                            case 'carte': $badge_icon = '💳'; break;
                                            case 'especes': $badge_icon = '💵'; break;
                                            case 'mobile': $badge_icon = '📱'; break;
                                            default: $badge_icon = '💳';
                                        }
                                        ?>
                                        <span class="mode-badge <?php echo $badge_class; ?>">
                                            <?php echo $badge_icon; ?> <?php echo ucfirst($paiement['mode_paiement']); ?>
                                        </span>
                                        <span> • </span>
                                        <span><?php echo date('H:i', strtotime($paiement['date_facture'])); ?></span>
                                        <span> • </span>
                                        <a href="generer_facture.php?facture=<?php echo $paiement['num_facture']; ?>" target="_blank" style="color: var(--primary-color); text-decoration: none; font-size: 0.8rem;">
                                            📄 Facture
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal de paiement -->
    <div class="modal-overlay" id="paiementModal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title">Paiement de la commande</div>
                <button class="modal-close" onclick="closeModal()">×</button>
            </div>
            
            <div class="modal-body">
                <?php if ($commande_details): ?>
                <div class="commande-info-grid">
                    <div class="info-item">
                        <div class="info-label">N° Commande</div>
                        <div class="info-value">#<?php echo str_pad($commande_details['num_commande'], 5, '0', STR_PAD_LEFT); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Table</div>
                        <div class="info-value"><?php echo $commande_details['num_table'] ?? 'N/A'; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Client</div>
                        <div class="info-value"><?php echo $commande_details['nom_client'] ?? 'Client'; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Heure</div>
                        <div class="info-value"><?php echo date('H:i', strtotime($commande_details['date_commande'])); ?></div>
                    </div>
                </div>
                
                <div class="items-list">
                    <?php 
                    $items = explode('||', $commande_details['details_items']);
                    foreach ($items as $item):
                        if (!empty($item)):
                            // Extraire les informations de l'item
                            preg_match('/(.+?) \(x(\d+)\) - ([\d.]+)€ = ([\d.]+)€/', $item, $matches);
                            if (count($matches) >= 5):
                    ?>
                    <div class="item-row">
                        <div class="item-name"><?php echo htmlspecialchars($matches[1]); ?></div>
                        <div class="item-quantity">x<?php echo $matches[2]; ?></div>
                        <div class="item-price"><?php echo $matches[4]; ?>€</div>
                    </div>
                    <?php 
                            endif;
                        endif;
                    endforeach; 
                    ?>
                </div>
                
                <div class="total-row">
                    <div>Total à payer</div>
                    <div>€<?php echo number_format($commande_details['montant_total'], 2); ?></div>
                </div>
                
                <form method="POST" id="paiementForm">
                    <input type="hidden" name="commande_id" value="<?php echo $commande_details['num_commande']; ?>">
                    <input type="hidden" name="montant_paye" value="<?php echo $commande_details['montant_total']; ?>">
                    <input type="hidden" name="mode_paiement" value="carte" id="selectedMode">
                    
                    <div class="paiement-options">
                        <div class="paiement-option active" data-mode="carte">
                            <div class="option-icon">💳</div>
                            <div class="option-label">Carte</div>
                        </div>
                        <div class="paiement-option" data-mode="especes">
                            <div class="option-icon">💵</div>
                            <div class="option-label">Espèces</div>
                        </div>
                        <div class="paiement-option" data-mode="mobile">
                            <div class="option-icon">📱</div>
                            <div class="option-label">Mobile</div>
                        </div>
                    </div>
                    
                    <button type="submit" name="payer_commande" class="btn-confirm">
                        ✅ Confirmer le paiement de €<?php echo number_format($commande_details['montant_total'], 2); ?>
                    </button>
                </form>
                <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">❌</div>
                    <p>Impossible de charger les détails de la commande</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script>
        // Gestion du modal
        function openModal(event, commandeId) {
            event.preventDefault();
            document.getElementById('paiementModal').style.display = 'flex';
            
            // Si on a déjà les détails (via URL), on les affiche
            // Sinon, on pourrait charger via AJAX
        }
        
        function closeModal() {
            document.getElementById('paiementModal').style.display = 'none';
            // Rediriger pour fermer le paramètre URL
            window.history.replaceState({}, document.title, window.location.pathname);
        }
        
        // Fermer le modal en cliquant à l'extérieur
        document.getElementById('paiementModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
        
        // Gestion des options de paiement
        document.addEventListener('DOMContentLoaded', function() {
            const options = document.querySelectorAll('.paiement-option');
            const modeInput = document.getElementById('selectedMode');
            
            options.forEach(option => {
                option.addEventListener('click', function() {
                    // Retirer la classe active de toutes les options
                    options.forEach(opt => opt.classList.remove('active'));
                    
                    // Ajouter la classe active à l'option cliquée
                    this.classList.add('active');
                    
                    // Mettre à jour l'input hidden
                    const mode = this.getAttribute('data-mode');
                    modeInput.value = mode;
                });
            });
            
            // Ouvrir le modal si on a un paramètre dans l'URL
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('voir_commande')) {
                document.getElementById('paiementModal').style.display = 'flex';
            }
        });
    </script>
</body>
</html>