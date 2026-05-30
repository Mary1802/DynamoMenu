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

require_once __DIR__ . '/../includes/dashboard_helpers.php';
require_once __DIR__ . '/../includes/table_context.php';
table_ensure_schema($pdo);

// Traiter le paiement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payer_commande'])) {
    $commande_id = $_POST['commande_id'];
    $mode_paiement = $_POST['mode_paiement'];
    $montant_paye = $_POST['montant_paye'];
    
    // Commencer une transaction
    $pdo->beginTransaction();
    
    try {
        // Créer la facture (commande déjà livrée)
        $stmt = $pdo->prepare("INSERT INTO facture (num_commande, total_paye, mode_paiement) VALUES (?, ?, ?)");
        $stmt->execute([$commande_id, $montant_paye, $mode_paiement]);
        
        // Mettre à jour les demandes de paiement (si la table existe)
        try {
            $stmt = $pdo->prepare("UPDATE demande_paiement SET statut = 'traitee', date_traitement = NOW() WHERE num_commande = ? AND statut = 'en_attente'");
            $stmt->execute([$commande_id]);
        } catch (PDOException $e) {
            // Table absente sur anciennes installations
        }
        
        $pdo->commit();

        require_once __DIR__ . '/../includes/fidelity_service.php';
        fidelity_award_after_payment($pdo, (int) $commande_id);
        
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

    try {
        $stmt = $pdo->prepare("UPDATE demande_paiement SET statut = 'annulee', date_traitement = NOW() WHERE id_demande = ?");
        $stmt->execute([$demande_id]);
    } catch (PDOException $e) {
        // Ignorer si table absente
    }

    header('Location: paiement.php');
    exit;
}

// Commandes livrées, pas encore facturées
$stmt = $pdo->prepare("
    SELECT 
        c.num_commande,
        c.date_commande,
        c.montant_total,
        c.mode_paiement_souhaite,
        t.num_table,
        cl.nom_client,
        cl.prenom_client,
        cl.email_client,
        cl.telephone_client,
        COUNT(d.id_detail) as nombre_items
    FROM commande c
    LEFT JOIN table_restaurant t ON c.num_table = t.num_table
    LEFT JOIN client cl ON c.id_client = cl.id_client
    LEFT JOIN contient d ON c.num_commande = d.num_commande
    LEFT JOIN facture f ON f.num_commande = c.num_commande
    WHERE c.statut = 'livree' AND f.num_facture IS NULL
    GROUP BY c.num_commande, c.date_commande, c.montant_total, c.mode_paiement_souhaite,
             t.num_table, cl.nom_client, cl.prenom_client, cl.email_client, cl.telephone_client
    ORDER BY c.date_commande ASC
");
$commandes_a_payer = [];
$dashboard_error = null;
try {
    $stmt->execute();
    $commandes_a_payer = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $dashboard_error = 'Impossible de charger les commandes à payer. Vérifiez la base de données (init_db.php ou run_update.php).';
}

// Récupérer les détails d'une commande spécifique (pour le modal)
$commande_details = null;
if (isset($_GET['voir_commande'])) {
    $commande_id = $_GET['voir_commande'];
    $stmt = $pdo->prepare("
        SELECT 
            c.num_commande,
            c.date_commande,
            c.montant_total,
            c.statut,
            c.mode_paiement_souhaite,
            c.id_client,
            c.num_table,
            t.num_table AS table_num,
            cl.nom_client,
            GROUP_CONCAT(
                CONCAT(
                    COALESCE(p.nom_plat, b.nom_boisson),
                    ' (x', d.quantite, ') - ',
                    d.prix, '€ = ',
                    d.sous_total, '€'
                ) SEPARATOR '||'
            ) AS details_items
        FROM commande c
        LEFT JOIN table_restaurant t ON c.num_table = t.num_table
        LEFT JOIN client cl ON c.id_client = cl.id_client
        LEFT JOIN contient d ON c.num_commande = d.num_commande
        LEFT JOIN plat p ON d.id_plat = p.id_plat
        LEFT JOIN boisson b ON d.id_boisson = b.id_boisson
        WHERE c.num_commande = ?
        GROUP BY c.num_commande, c.date_commande, c.montant_total, c.statut, c.mode_paiement_souhaite,
                 c.id_client, c.num_table, t.num_table, cl.nom_client
    ");
    $stmt->execute([$commande_id]);
    $commande_details = $stmt->fetch(PDO::FETCH_ASSOC);
}

$paiements_recents = [];
try {
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
} catch (PDOException $e) {
    $paiements_recents = [];
}

$demandes_paiement = dashboard_fetch_demandes_paiement($pdo);

$stats_jour = [
    'total_paiements' => 0,
    'total_ca' => 0,
    'moyenne_paiement' => 0,
];
try {
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) AS total_paiements,
            COALESCE(SUM(total_paye), 0) AS total_ca,
            COALESCE(AVG(total_paye), 0) AS moyenne_paiement
        FROM facture 
        WHERE DATE(date_facture) = CURDATE()
    ");
    $stmt->execute();
    $stats_jour = $stmt->fetch(PDO::FETCH_ASSOC) ?: $stats_jour;
} catch (PDOException $e) {
    // Table facture absente ou schéma incomplet
}
?>
<!doctype html>
<html lang="fr">
<head>
    <?php dashboard_asset_links('Caissier - Paiement des commandes'); ?>
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

    <div class="dashboard-shell dashboard-container">
        <aside class="dashboard-sidebar d-flex flex-column" id="dashboardSidebar">
            <div class="sidebar-brand">
                <div class="brand-logo">DM</div>
                <div class="brand-title">DynamoMenu</div>
                <div class="brand-subtitle">Caisse</div>
            </div>
            
            <nav class="sidebar-nav">
                <div class="nav-item">
                    <a class="nav-link active" href="paiement.php">
                        <span class="nav-icon"><i class="bi bi-credit-card" aria-hidden="true"></i></span>
                        <span>Paiements</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link" href="rapports.php">
                        <span class="nav-icon"><i class="bi bi-file-earmark-bar-graph" aria-hidden="true"></i></span>
                        <span>Rapports</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link" href="#">
                        <span class="nav-icon"><i class="bi bi-gear" aria-hidden="true"></i></span>
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
                    <span class="header-eyebrow">Caisse</span>
                    <h1>Paiement des commandes</h1>
                    <p>Facturation des commandes livrées aux tables</p>
                </div>
                
                <div class="header-actions">
                    <div class="search-box">
                        <input type="search" class="search-input" placeholder="Rechercher une commande..." aria-label="Rechercher une commande">
                        <span class="search-icon"><i class="bi bi-search" aria-hidden="true"></i></span>
                    </div>
                    
                    <a href="#" class="notification-btn" aria-label="Commandes à payer">
                        <i class="bi bi-bell" aria-hidden="true"></i>
                        <span class="notification-badge"><?php echo count($commandes_a_payer); ?></span>
                    </a>
                </div>
            </header>

            <?php if (isset($_GET['success'])): ?>
            <div class="success-message" role="status">
                <i class="bi bi-check-circle" aria-hidden="true"></i>
                Paiement de la commande #<?php echo htmlspecialchars($_GET['commande'] ?? '', ENT_QUOTES, 'UTF-8'); ?> effectué avec succès.
            </div>
            <?php endif; ?>

            <?php if (!empty($dashboard_error)): ?>
            <div class="success-message" style="color: var(--danger-color); border-color: rgba(220,53,69,0.35); background: rgba(220,53,69,0.1);">
                <?php echo htmlspecialchars($dashboard_error); ?>
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
                <div class="commandes-section mb-4">
                    <div class="section-title">
                        Demandes de paiement
                        <span class="section-count">(<?php echo count($demandes_paiement); ?> en attente)</span>
                    </div>
                    
                    <div class="row g-3">
                        <?php foreach ($demandes_paiement as $demande): ?>
                        <div class="col-12">
                            <div class="commande-item demande-highlight">
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
                                        $mode_labels = [
                                            'carte' => 'Carte',
                                            'especes' => 'Espèces',
                                            'mobile' => 'Mobile',
                                        ];
                                        ?>
                                        <span class="mode-badge mode-<?php echo htmlspecialchars($demande['mode_paiement'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php echo $mode_labels[$demande['mode_paiement']] ?? ucfirst($demande['mode_paiement']); ?>
                                        </span>
                                        <span> • </span>
                                        <span><?php echo date('H:i', strtotime($demande['date_demande'])); ?></span>
                                    </div>
                                </div>
                                
                                <div class="commande-actions">
                                    <a href="?voir_commande=<?php echo $demande['num_commande']; ?>" class="btn-details" onclick="openModal(event, <?php echo $demande['num_commande']; ?>)">
                                        <i class="bi bi-list-ul" aria-hidden="true"></i> Voir détails
                                    </a>
                                    <button type="button" class="btn-payer" onclick="openModal(event, <?php echo $demande['num_commande']; ?>)">
                                        <i class="bi bi-cash-coin" aria-hidden="true"></i> Traiter le paiement
                                    </button>
                                    <form method="POST" class="d-flex flex-grow-1">
                                        <input type="hidden" name="demande_id" value="<?php echo $demande['id_demande']; ?>">
                                        <button type="submit" name="annuler_demande" class="btn-details btn-danger-outline w-100">
                                            <i class="bi bi-x-lg" aria-hidden="true"></i> Annuler
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
                        Commandes livrées à encaisser
                        <span class="section-count">(<?php echo count($commandes_a_payer); ?> en attente)</span>
                    </div>
                    
                    <?php if (empty($commandes_a_payer)): ?>
                    <div class="empty-state">
                        <div class="empty-icon"><i class="bi bi-wallet2" aria-hidden="true"></i></div>
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
                                        <span><?php echo htmlspecialchars(trim(($commande['prenom_client'] ?? '') . ' ' . ($commande['nom_client'] ?? 'Client'))); ?></span>
                                        <?php if (!empty($commande['telephone_client'])): ?>
                                        <span> • <?php echo htmlspecialchars($commande['telephone_client']); ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($commande['mode_paiement_souhaite'])): ?>
                                        <span> • <?php echo $commande['mode_paiement_souhaite'] === 'mobile_money' ? 'Mobile money' : 'Cash'; ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <span><?php echo $commande['nombre_items']; ?> article(s)</span>
                                        <span> • </span>
                                        <span><?php echo date('H:i', strtotime($commande['date_commande'])); ?></span>
                                    </div>
                                </div>
                                
                                <div class="commande-actions">
                                    <a href="?voir_commande=<?php echo $commande['num_commande']; ?>" class="btn-details" onclick="openModal(event, <?php echo $commande['num_commande']; ?>)">
                                        <i class="bi bi-list-ul" aria-hidden="true"></i> Voir détails
                                    </a>
                                    <button type="button" class="btn-payer" onclick="openModal(event, <?php echo $commande['num_commande']; ?>)">
                                        <i class="bi bi-cash-coin" aria-hidden="true"></i> Payer maintenant
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
                    <div class="empty-state py-3">
                        <div class="empty-icon"><i class="bi bi-receipt" aria-hidden="true"></i></div>
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
                                        <span class="mode-badge mode-<?php echo htmlspecialchars($paiement['mode_paiement'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php echo ucfirst($paiement['mode_paiement']); ?>
                                        </span>
                                        <span> • </span>
                                        <span><?php echo date('H:i', strtotime($paiement['date_facture'])); ?></span>
                                        <span> • </span>
                                        <a href="generer_facture.php?facture=<?php echo $paiement['num_facture']; ?>" target="_blank" rel="noopener" class="link-invoice">
                                            <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> Facture
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
    </div><!-- /.dashboard-shell -->

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
                        <div class="info-value"><?php echo $commande_details['table_num'] ?? $commande_details['num_table'] ?? 'N/A'; ?></div>
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
                
                <?php
                $defaultMode = 'especes';
                if (!empty($commande_details['mode_paiement_souhaite'])) {
                    $defaultMode = $commande_details['mode_paiement_souhaite'] === 'mobile_money' ? 'mobile' : 'especes';
                }
                ?>
                <form method="POST" id="paiementForm">
                    <input type="hidden" name="commande_id" value="<?php echo $commande_details['num_commande']; ?>">
                    <input type="hidden" name="montant_paye" value="<?php echo $commande_details['montant_total']; ?>">
                    <input type="hidden" name="mode_paiement" value="<?php echo htmlspecialchars($defaultMode); ?>" id="selectedMode">
                    
                    <div class="paiement-options">
                        <div class="paiement-option<?php echo $defaultMode === 'especes' ? ' active' : ''; ?>" data-mode="especes" role="button" tabindex="0">
                            <div class="option-icon"><i class="bi bi-cash-stack" aria-hidden="true"></i></div>
                            <div class="option-label">Cash</div>
                        </div>
                        <div class="paiement-option<?php echo $defaultMode === 'mobile' ? ' active' : ''; ?>" data-mode="mobile" role="button" tabindex="0">
                            <div class="option-icon"><i class="bi bi-phone" aria-hidden="true"></i></div>
                            <div class="option-label">Mobile money</div>
                        </div>
                    </div>
                    
                    <button type="submit" name="payer_commande" class="btn-confirm">
                        <i class="bi bi-check-lg" aria-hidden="true"></i>
                        Confirmer le paiement de <?php echo number_format($commande_details['montant_total'], 2, ',', ' '); ?> €
                    </button>
                </form>
                <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="bi bi-exclamation-circle" aria-hidden="true"></i></div>
                    <p>Impossible de charger les détails de la commande</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php dashboard_scripts(); ?>
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