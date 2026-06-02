<?php
session_start();

// Vérifier qu'une demande de paiement existe
if (!isset($_SESSION['demande_paiement'])) {
    header('Location: index.php');
    exit;
}

$demande = $_SESSION['demande_paiement'];
$commande_id = $_GET['commande'] ?? $demande['commande_id'];

// Configuration de la base de données
$db_config = require '../config/db.php';
require_once __DIR__ . '/../includes/money.php';
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

// Récupérer les détails de la commande
$stmt = $pdo->prepare("
    SELECT c.*, t.num_table, cl.nom_client, cl.prenom_client
    FROM commande c
    LEFT JOIN table_restaurant t ON c.num_table = t.num_table
    LEFT JOIN client cl ON c.id_client = cl.id_client
    WHERE c.num_commande = ?
");
$stmt->execute([$commande_id]);
$commande = $stmt->fetch(PDO::FETCH_ASSOC);

// Effacer la demande de paiement après affichage
unset($_SESSION['demande_paiement']);
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Demande de paiement envoyée - DynamoMenu</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .confirmation-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 3rem 1rem;
            text-align: center;
        }
        
        .confirmation-icon {
            font-size: 4rem;
            color: #28a745;
            margin-bottom: 1.5rem;
        }
        
        .confirmation-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            padding: 2.5rem;
            margin-top: 2rem;
        }
        
        .demande-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            margin: 1.5rem 0;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #eee;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #ff6f1f, #ff8a3d);
            border: none;
            border-radius: 8px;
            color: white;
            padding: 1rem 2rem;
            font-weight: 600;
            font-size: 1rem;
            text-decoration: none;
            display: inline-block;
            margin: 0.5rem;
            transition: all 0.2s ease;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #ff8a3d, #ff6f1f);
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(255, 111, 31, 0.3);
            color: white;
        }
        
        .instructions-box {
            background: #e7f4e4;
            border: 1px solid #c3e6cb;
            border-radius: 8px;
            padding: 1.5rem;
            margin-top: 2rem;
            text-align: left;
        }
        
        .mode-icon {
            font-size: 1.5rem;
            margin-right: 0.5rem;
        }
        
        .mode-text {
            display: inline-flex;
            align-items: center;
            background: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            border: 1px solid #ddd;
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
        </div>
    </nav>

    <div class="confirmation-container">
        <div class="confirmation-icon">✅</div>
        
        <h1>Demande de paiement envoyée !</h1>
        <p class="lead">Le caissier a été notifié et viendra à votre table pour finaliser le paiement.</p>
        
        <div class="confirmation-card">
            <h3>Commande #<?php echo str_pad($commande_id, 5, '0', STR_PAD_LEFT); ?></h3>
            
            <div class="demande-info">
                <div class="info-row">
                    <span>Numéro de commande</span>
                    <span><strong>#<?php echo str_pad($commande_id, 5, '0', STR_PAD_LEFT); ?></strong></span>
                </div>
                <div class="info-row">
                    <span>Table</span>
                    <span><strong>Table <?php echo $commande['num_table']; ?></strong></span>
                </div>
                <div class="info-row">
                    <span>Client</span>
                    <span><strong><?php echo htmlspecialchars($commande['prenom_client'] . ' ' . $commande['nom_client']); ?></strong></span>
                </div>
                <div class="info-row">
                    <span>Montant à payer</span>
                    <span><strong><?php echo format_money((float) $demande['montant']); ?></strong></span>
                </div>
                <div class="info-row">
                    <span>Mode de paiement choisi</span>
                    <span>
                        <div class="mode-text">
                            <?php 
                            $mode_icons = [
                                'carte' => '💳',
                                'especes' => '💵',
                                'mobile' => '📱'
                            ];
                            $mode_labels = [
                                'carte' => 'Carte bancaire',
                                'especes' => 'Espèces',
                                'mobile' => 'Paiement mobile'
                            ];
                            ?>
                            <span class="mode-icon"><?php echo $mode_icons[$demande['mode_paiement']] ?? '💳'; ?></span>
                            <span><?php echo $mode_labels[$demande['mode_paiement']] ?? ucfirst($demande['mode_paiement']); ?></span>
                        </div>
                    </span>
                </div>
            </div>
            
            <div class="instructions-box">
                <h5>📋 Prochaines étapes :</h5>
                <ol>
                    <li>Le caissier a reçu votre demande de paiement</li>
                    <li>Il viendra à la table <?php echo $commande['num_table']; ?> avec le terminal de paiement</li>
                    <li>Présentez votre <?php echo $mode_labels[$demande['mode_paiement']] ?? 'moyen de paiement'; ?></li>
                    <li>Le caissier validera la transaction</li>
                    <li>Vous recevrez une facture électronique ou papier</li>
                </ol>
                
                <p style="margin-top: 1rem; font-style: italic;">
                    <strong>Note :</strong> Le temps d'attente estimé est de 2-3 minutes.
                </p>
            </div>
            
            <div style="margin-top: 2rem;">
                <a href="index.php" class="btn-primary">
                    Retour à l'accueil
                </a>
                <a href="paiement_client.php?commande=<?php echo $commande_id; ?>" class="btn-primary" style="background: #6c757d;">
                    Voir les détails de la commande
                </a>
            </div>
        </div>
    </div>

    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script>
        // Redirection automatique après 30 secondes
        setTimeout(() => {
            window.location.href = 'index.php';
        }, 30000);
        
        // Compte à rebours
        let countdown = 30;
        const countdownElement = document.createElement('div');
        countdownElement.style.cssText = 'margin-top: 1rem; color: #666; font-size: 0.9rem;';
        countdownElement.innerHTML = `Redirection automatique dans <span id="countdown">${countdown}</span> secondes...`;
        document.querySelector('.confirmation-card').appendChild(countdownElement);
        
        const countdownInterval = setInterval(() => {
            countdown--;
            document.getElementById('countdown').textContent = countdown;
            
            if (countdown <= 0) {
                clearInterval(countdownInterval);
            }
        }, 1000);
    </script>
</body>
</html>