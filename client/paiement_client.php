<?php
require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/../includes/client_session.php';
require_once __DIR__ . '/../includes/money.php';

use App\Controller\Client\PaymentController;
use App\Service\ClientPaymentService;

$data = (new PaymentController())->show($_GET);
if ($data === null) {
    header('Location: index.php');
    exit;
}

$num_commande = $data['num_commande'];
$commande = $data['commande'];
$articles = $data['articles'];
$est_payee = $data['est_payee'];
$status_text = ClientPaymentService::statusLabels();
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Paiement - Commande #<?php echo str_pad($num_commande, 5, '0', STR_PAD_LEFT); ?> - DynamoMenu</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <?php csrf_meta_tag(); ?>
    <style>
        .paiement-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }
        
        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid #ff6f1f;
        }
        
        .paiement-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .commande-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
        }
        
        .info-label {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 0.25rem;
        }
        
        .info-value {
            font-size: 1rem;
            color: #333;
            font-weight: 500;
        }
        
        .articles-list {
            margin-bottom: 2rem;
        }
        
        .article-item {
            display: flex;
            justify-content: space-between;
            padding: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .article-item:last-child {
            border-bottom: none;
        }
        
        .article-details {
            flex: 2;
        }
        
        .article-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.25rem;
        }
        
        .article-options {
            font-size: 0.9rem;
            color: #666;
        }
        
        .article-price {
            font-weight: 600;
            color: #333;
            min-width: 100px;
            text-align: right;
        }
        
        .total-section {
            display: flex;
            justify-content: space-between;
            padding: 1.5rem;
            border-top: 2px solid #ddd;
            font-weight: 600;
            font-size: 1.2rem;
            color: #ff6f1f;
            background: #f8f9fa;
            border-radius: 0 0 8px 8px;
        }
        
        .paiement-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin: 2rem 0;
        }
        
        .paiement-option {
            background: white;
            border: 2px solid #ddd;
            border-radius: 8px;
            padding: 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .paiement-option:hover,
        .paiement-option.selected {
            border-color: #ff6f1f;
            background: rgba(255, 111, 31, 0.1);
        }
        
        .option-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .option-label {
            font-weight: 500;
            color: #333;
        }
        
        .option-desc {
            font-size: 0.85rem;
            color: #666;
            margin-top: 0.25rem;
        }
        
        .btn-payer {
            background: linear-gradient(135deg, #ff6f1f, #ff8a3d);
            border: none;
            border-radius: 8px;
            color: white;
            padding: 1rem 2rem;
            font-weight: 600;
            font-size: 1rem;
            width: 100%;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .btn-payer:hover {
            background: linear-gradient(135deg, #ff8a3d, #ff6f1f);
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(255, 111, 31, 0.3);
        }
        
        .btn-payer:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .facture-section {
            background: #e7f4e4;
            border: 1px solid #c3e6cb;
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
        }
        
        .facture-icon {
            font-size: 3rem;
            color: #28a745;
            margin-bottom: 1rem;
        }
        
        .facture-details {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-top: 1.5rem;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
            margin-bottom: 1rem;
        }
        
        .status-en-attente {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .status-en-preparation {
            background: #cce5ff;
            color: #004085;
            border: 1px solid #b8daff;
        }
        
        .status-prete {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .status-livree {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .status-payee {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <span style="color: #ff6f1f; font-weight: 700;">Dynamo</span><span style="color: #333;">Menu</span>
            </a>
            <div class="d-flex align-items-center">
                <a href="index.php" class="btn btn-outline-secondary">← Retour à l'accueil</a>
            </div>
        </div>
    </nav>

    <div class="paiement-container">
        <h1 class="mb-4">
            <?php if ($est_payee): ?>
            ✅ Facture de la commande
            <?php else: ?>
            💳 Paiement de la commande
            <?php endif; ?>
        </h1>
        
        <div class="paiement-card">
            <!-- En-tête de la commande -->
            <div class="commande-info">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h3 style="margin: 0;">Commande #<?php echo str_pad($num_commande, 5, '0', STR_PAD_LEFT); ?></h3>
                        <p style="color: #666; margin: 0.25rem 0 0 0;">
                            Table <?php echo $commande['num_table']; ?> • 
                            <?php echo date('d/m/Y H:i', strtotime($commande['date_commande'])); ?>
                        </p>
                    </div>
                    
                    <?php
                    $status_class = 'status-' . str_replace('_', '-', $commande['statut']);
                    ?>
                    <div class="status-badge <?php echo $status_class; ?>">
                        <?php echo $status_text[$commande['statut']] ?? $commande['statut']; ?>
                    </div>
                </div>
                
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Client</div>
                        <div class="info-value">
                            <?php echo htmlspecialchars($commande['prenom_client'] . ' ' . $commande['nom_client']); ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Email</div>
                        <div class="info-value"><?php echo htmlspecialchars($commande['email_client']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Téléphone</div>
                        <div class="info-value"><?php echo htmlspecialchars($commande['telephone_client']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Table</div>
                        <div class="info-value">Table <?php echo $commande['num_table']; ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Détails des articles -->
            <h3 class="section-title">Détails de la commande</h3>
            
            <div class="articles-list">
                <?php foreach ($articles as $article): ?>
                <div class="article-item">
                    <div class="article-details">
                        <div class="article-name">
                            <?php 
                            if (!empty($article['nom_plat'])) {
                                echo htmlspecialchars($article['nom_plat']);
                            } elseif (!empty($article['nom_boisson'])) {
                                echo htmlspecialchars($article['nom_boisson']);
                            }
                            ?>
                        </div>
                        <div class="article-options">
                            <?php if (!empty($article['sauces'])): ?>
                            <div>Sauces: <?php echo htmlspecialchars($article['sauces']); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($article['personnalisation_boisson'])): ?>
                            <div>Personnalisation: <?php echo htmlspecialchars($article['personnalisation_boisson']); ?></div>
                            <?php endif; ?>
                            <div>Quantité: x<?php echo $article['quantite']; ?></div>
                        </div>
                    </div>
                    <div class="article-price">
                        <?php echo format_money((float) $article['sous_total']); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Total -->
            <div class="total-section">
                <div>Total à payer</div>
                <div><?php echo format_money((float) $commande['montant_total']); ?></div>
            </div>
            
            <?php if ($est_payee): ?>
            <!-- Facture déjà générée -->
            <div class="facture-section">
                <div class="facture-icon">✅</div>
                <h3>Facture déjà payée</h3>
                <p>Votre commande a été réglée le <?php echo date('d/m/Y à H:i', strtotime($commande['date_facture'])); ?></p>
                
                <div class="facture-details">
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Numéro de facture</div>
                            <div class="info-value">F-<?php echo str_pad($commande['num_facture'], 4, '0', STR_PAD_LEFT); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Mode de paiement</div>
                            <div class="info-value">
                                <?php
                                $mode_icons = ClientPaymentService::modeIcons();
                                echo $mode_icons[$commande['mode_paiement']] ?? ucfirst((string) $commande['mode_paiement']);
                                ?>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Montant payé</div>
                            <div class="info-value"><?php echo format_money((float) $commande['total_paye']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Date du paiement</div>
                            <div class="info-value"><?php echo date('d/m/Y H:i', strtotime($commande['date_facture'])); ?></div>
                        </div>
                    </div>
                </div>
                
                <a href="index.php" class="btn-payer" style="margin-top: 2rem; max-width: 300px;">
                    Retour à l'accueil
                </a>
            </div>
            
            <?php elseif ($commande['statut'] === 'prete' || $commande['statut'] === 'livree'): ?>
            <!-- Interface de paiement -->
            <h3 class="section-title">Choisissez votre mode de paiement</h3>
            
            <div class="paiement-options">
                <div class="paiement-option" data-mode="carte">
                    <div class="option-icon">💳</div>
                    <div class="option-label">Carte bancaire</div>
                    <div class="option-desc">Paiement sécurisé</div>
                </div>
                
                <div class="paiement-option" data-mode="especes">
                    <div class="option-icon">💵</div>
                    <div class="option-label">Espèces</div>
                    <div class="option-desc">Paiement en liquide</div>
                </div>
                
                <div class="paiement-option" data-mode="mobile">
                    <div class="option-icon">📱</div>
                    <div class="option-label">Mobile</div>
                    <div class="option-desc">Apple Pay / Google Pay</div>
                </div>
            </div>
            
            <div style="text-align: center; margin: 2rem 0;">
                <p style="color: #666; font-size: 0.9rem;">
                    Le caissier viendra à votre table pour finaliser le paiement avec le mode choisi.
                </p>
            </div>
            
            <form id="paiementForm" method="POST" action="traitement_paiement.php">
                <?php csrf_field(); ?>
                <input type="hidden" name="commande_id" value="<?php echo $num_commande; ?>">
                <input type="hidden" name="mode_paiement" id="selectedMode" value="">
                <input type="hidden" name="montant" value="<?php echo $commande['montant_total']; ?>">
                
                <button type="submit" class="btn-payer" id="btnPayer" disabled>
                    Confirmer le choix de paiement
                </button>
            </form>
            
            <?php else: ?>
            <!-- Commande pas encore prête -->
            <div class="facture-section" style="background: #fff3cd; border-color: #ffeaa7;">
                <div class="facture-icon" style="color: #856404;">⏳</div>
                <h3 style="color: #856404;">Commande en préparation</h3>
                <p>Votre commande est encore en cours de préparation. Le paiement sera disponible une fois la commande prête à servir.</p>
                
                <div style="margin-top: 1.5rem;">
                    <a href="index.php" class="btn-payer" style="background: #6c757d; max-width: 300px;">
                        Retour à l'accueil
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="../assets/js/csrf.js?v=1"></script>
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script>
        // Gestion de la sélection du mode de paiement
        const options = document.querySelectorAll('.paiement-option');
        const selectedModeInput = document.getElementById('selectedMode');
        const btnPayer = document.getElementById('btnPayer');
        
        if (options.length > 0) {
            options.forEach(option => {
                option.addEventListener('click', function() {
                    // Retirer la sélection de toutes les options
                    options.forEach(opt => {
                        opt.classList.remove('selected');
                    });
                    
                    // Ajouter la sélection à l'option cliquée
                    this.classList.add('selected');
                    
                    // Mettre à jour l'input hidden
                    const mode = this.getAttribute('data-mode');
                    selectedModeInput.value = mode;
                    
                    // Activer le bouton de paiement
                    btnPayer.disabled = false;
                    btnPayer.innerHTML = `Confirmer le paiement par ${this.querySelector('.option-label').textContent}`;
                });
            });
            
            // Sélectionner la première option par défaut
            options[0].click();
        }
        
        // Gestion du formulaire de paiement
        const paiementForm = document.getElementById('paiementForm');
        if (paiementForm) {
            paiementForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const mode = selectedModeInput.value;
                if (!mode) {
                    alert('Veuillez choisir un mode de paiement');
                    return;
                }
                
                // Afficher un message de confirmation
                const confirmation = confirm(
                    `Confirmez-vous le paiement de <?php echo json_encode(format_money((float) $commande['montant_total']), JSON_UNESCAPED_UNICODE); ?> par ${mode} ?\n\n` +
                    'Le caissier viendra à votre table pour finaliser la transaction.'
                );
                
                if (confirmation) {
                    // Soumettre le formulaire
                    this.submit();
                }
            });
        }
    </script>
</body>
</html>