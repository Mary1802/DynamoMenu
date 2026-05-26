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

// Récupérer le numéro de facture
$num_facture = $_GET['facture'] ?? null;
if (!$num_facture) {
    header('Location: paiement.php');
    exit;
}

// Récupérer les détails de la facture
$stmt = $pdo->prepare("
    SELECT 
        f.*,
        c.num_commande,
        c.date_commande,
        c.montant_total,
        c.statut,
        t.num_table,
        cl.nom_client,
        cl.prenom_client,
        cl.email_client,
        cl.telephone_client
    FROM facture f
    JOIN commande c ON f.num_commande = c.num_commande
    LEFT JOIN table_restaurant t ON c.num_table = t.num_table
    LEFT JOIN client cl ON c.id_client = cl.id_client
    WHERE f.num_facture = ?
");
$stmt->execute([$num_facture]);
$facture = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$facture) {
    header('Location: paiement.php');
    exit;
}

// Récupérer les détails des articles
$boissonColumns = array_column($pdo->query("SHOW COLUMNS FROM boisson")->fetchAll(PDO::FETCH_ASSOC), 'Field');
$typeBoissonTableExists = count($pdo->query("SHOW TABLES LIKE 'type_boisson'")->fetchAll(PDO::FETCH_ASSOC)) > 0;
$boissonSelect = "b.nom_boisson";
$boissonJoin = "";
if (in_array('type_boisson', $boissonColumns, true)) {
    $boissonSelect .= ", b.type_boisson";
} elseif ($typeBoissonTableExists && in_array('id_boisson', $boissonColumns, true)) {
    $boissonSelect .= ", tb.nom_type AS type_boisson";
    $boissonJoin = "LEFT JOIN type_boisson tb ON b.id_boisson = tb.id_boisson";
}

$stmt = $pdo->prepare("
    SELECT 
        d.*,
        p.nom_plat,
        p.prix_unitaire as prix_plat,
        $boissonSelect
    FROM contient d
    LEFT JOIN plat p ON d.id_plat = p.id_plat
    LEFT JOIN boisson b ON d.id_boisson = b.id_boisson
    $boissonJoin
    WHERE d.num_commande = ?
");
$stmt->execute([$facture['num_commande']]);
$articles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculer la TVA (20%)
$tva = $facture['total_paye'] * 0.20;
$ht = $facture['total_paye'] - $tva;
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Facture F-<?php echo str_pad($num_facture, 4, '0', STR_PAD_LEFT); ?> - DynamoMenu</title>
    <style>
        @page {
            margin: 0;
        }
        
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        
        .facture-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border: 1px solid #ddd;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #ff6f1f;
        }
        
        .restaurant-info {
            flex: 1;
        }
        
        .restaurant-name {
            font-size: 24px;
            font-weight: bold;
            color: #ff6f1f;
            margin: 0 0 5px 0;
        }
        
        .restaurant-details {
            font-size: 12px;
            color: #666;
            line-height: 1.4;
        }
        
        .facture-info {
            text-align: right;
        }
        
        .facture-title {
            font-size: 28px;
            font-weight: bold;
            color: #333;
            margin: 0 0 10px 0;
        }
        
        .facture-number {
            font-size: 18px;
            color: #666;
            margin: 0;
        }
        
        .client-info {
            margin-bottom: 30px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #333;
            margin: 0 0 10px 0;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .info-item {
            margin-bottom: 5px;
        }
        
        .info-label {
            font-size: 12px;
            color: #666;
            margin-bottom: 2px;
        }
        
        .info-value {
            font-size: 14px;
            color: #333;
            font-weight: 500;
        }
        
        .articles-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0 30px 0;
        }
        
        .articles-table th {
            background: #f8f9fa;
            padding: 10px;
            text-align: left;
            font-size: 12px;
            font-weight: bold;
            color: #333;
            border-bottom: 2px solid #ddd;
        }
        
        .articles-table td {
            padding: 10px;
            font-size: 12px;
            border-bottom: 1px solid #eee;
        }
        
        .articles-table tr:last-child td {
            border-bottom: none;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .total-section {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #ddd;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            font-size: 14px;
        }
        
        .total-row.total {
            font-weight: bold;
            font-size: 16px;
            color: #ff6f1f;
            padding-top: 10px;
            border-top: 1px solid #ddd;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 10px;
            color: #666;
            text-align: center;
        }
        
        .signature {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
        
        .signature-line {
            width: 200px;
            border-top: 1px solid #333;
            margin: 20px 0 5px 0;
        }
        
        .print-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #ff6f1f;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }
        
        .print-btn:hover {
            background: #ff8a3d;
        }
        
        @media print {
            .print-btn {
                display: none;
            }
            
            body {
                padding: 0;
            }
            
            .facture-container {
                border: none;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <button class="print-btn" onclick="window.print()">🖨️ Imprimer la facture</button>
    
    <div class="facture-container">
        <!-- En-tête -->
        <div class="header">
            <div class="restaurant-info">
                <h1 class="restaurant-name">DynamoMenu</h1>
                <div class="restaurant-details">
                    123 Avenue du Restaurant<br>
                    75000 Paris, France<br>
                    Tél: 01 23 45 67 89<br>
                    Email: contact@dynamomenu.fr<br>
                    SIRET: 123 456 789 00012<br>
                    TVA Intracom: FR12345678901
                </div>
            </div>
            
            <div class="facture-info">
                <h2 class="facture-title">FACTURE</h2>
                <p class="facture-number">
                    N° F-<?php echo str_pad($num_facture, 4, '0', STR_PAD_LEFT); ?><br>
                    Date: <?php echo date('d/m/Y', strtotime($facture['date_facture'])); ?>
                </p>
            </div>
        </div>
        
        <!-- Informations client -->
        <div class="client-info">
            <h3 class="section-title">CLIENT</h3>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Nom</div>
                    <div class="info-value"><?php echo htmlspecialchars($facture['prenom_client'] . ' ' . $facture['nom_client']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Email</div>
                    <div class="info-value"><?php echo htmlspecialchars($facture['email_client']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Téléphone</div>
                    <div class="info-value"><?php echo htmlspecialchars($facture['telephone_client']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Commande</div>
                    <div class="info-value">N° <?php echo str_pad($facture['num_commande'], 5, '0', STR_PAD_LEFT); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Table</div>
                    <div class="info-value">Table <?php echo $facture['num_table']; ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Date commande</div>
                    <div class="info-value"><?php echo date('d/m/Y H:i', strtotime($facture['date_commande'])); ?></div>
                </div>
            </div>
        </div>
        
        <!-- Détails des articles -->
        <h3 class="section-title">DÉTAIL DE LA COMMANDE</h3>
        <table class="articles-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="text-center">Quantité</th>
                    <th class="text-right">Prix unitaire</th>
                    <th class="text-right">Total HT</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($articles as $article): ?>
                <tr>
                    <td>
                        <?php 
                        if (!empty($article['nom_plat'])) {
                            echo htmlspecialchars($article['nom_plat']);
                            if (!empty($article['sauces'])) {
                                echo '<br><small style="color: #666;">Sauces: ' . htmlspecialchars($article['sauces']) . '</small>';
                            }
                        } elseif (!empty($article['nom_boisson'])) {
                            echo htmlspecialchars($article['nom_boisson']);
                            if (!empty($article['personnalisation_boisson'])) {
                                echo '<br><small style="color: #666;">' . htmlspecialchars($article['personnalisation_boisson']) . '</small>';
                            }
                        }
                        ?>
                    </td>
                    <td class="text-center"><?php echo $article['quantite']; ?></td>
                    <td class="text-right">€<?php echo number_format($article['prix'], 2); ?></td>
                    <td class="text-right">€<?php echo number_format($article['sous_total'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- Totaux -->
        <div class="total-section">
            <div class="total-row">
                <span>Total HT</span>
                <span>€<?php echo number_format($ht, 2); ?></span>
            </div>
            <div class="total-row">
                <span>TVA (20%)</span>
                <span>€<?php echo number_format($tva, 2); ?></span>
            </div>
            <div class="total-row total">
                <span>TOTAL TTC</span>
                <span>€<?php echo number_format($facture['total_paye'], 2); ?></span>
            </div>
        </div>
        
        <!-- Mode de paiement -->
        <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
            <div class="section-title">MODE DE PAIEMENT</div>
            <div style="font-size: 14px;">
                <?php 
                $mode_labels = [
                    'carte' => '💳 Carte bancaire',
                    'especes' => '💵 Espèces',
                    'mobile' => '📱 Paiement mobile'
                ];
                echo $mode_labels[$facture['mode_paiement']] ?? ucfirst($facture['mode_paiement']);
                ?>
            </div>
        </div>
        
        <!-- Signature -->
        <div class="signature">
            <div style="font-size: 12px; color: #666; margin-bottom: 10px;">
                Fait à Paris, le <?php echo date('d/m/Y', strtotime($facture['date_facture'])); ?>
            </div>
            <div class="signature-line"></div>
            <div style="font-size: 12px; color: #666;">
                Signature et cachet du restaurant
            </div>
        </div>
        
        <!-- Pied de page -->
        <div class="footer">
            <p>
                DynamoMenu - Restaurant Gastronomique<br>
                Facture émise électroniquement - Conservez ce document pour vos archives<br>
                En cas de réclamation, merci de contacter le service client au 01 23 45 67 89
            </p>
        </div>
    </div>
    
    <script>
        // Impression automatique optionnelle
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('print')) {
            window.print();
        }
        
        // Ajouter un bouton de retour
        const backBtn = document.createElement('button');
        backBtn.innerHTML = '← Retour au dashboard';
        backBtn.style.cssText = 'position: fixed; top: 20px; left: 20px; background: #6c757d; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-weight: bold;';
        backBtn.onclick = () => window.location.href = 'paiement.php';
        document.body.appendChild(backBtn);
    </script>
</body>
</html>