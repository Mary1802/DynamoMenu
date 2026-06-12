<?php

require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/../includes/staff_auth.php';
require_once __DIR__ . '/../includes/money.php';
require_once __DIR__ . '/../includes/dashboard_helpers.php';

use App\Controller\Caissier\FactureController;

staff_require(['caissier']);

$result = (new FactureController())->show($_GET);
if ($result === null) {
    header('Location: paiement.php');
    exit;
}

$num_facture = $result['num_facture'];
$facture = $result['facture'];
$articles = $result['articles'];
$ht = $result['ht'];
$tva = $result['tva'];
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Facture F-<?php echo str_pad($num_facture, 4, '0', STR_PAD_LEFT); ?> - DynamoMenu</title>
    <style>
        @page {
            size: A4;
            margin: 15mm 12mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
        }

        .screen-only {
            position: fixed;
            z-index: 1000;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }

        .print-btn {
            top: 20px;
            right: 20px;
            background: #ff6f1f;
            color: white;
        }

        .print-btn:hover {
            background: #ff8a3d;
        }

        .back-btn {
            top: 20px;
            left: 20px;
            background: #6c757d;
            color: white;
        }

        .back-btn:hover {
            background: #5a6268;
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
            overflow-wrap: anywhere;
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
            overflow-wrap: anywhere;
            word-break: break-word;
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
            word-wrap: break-word;
            overflow-wrap: break-word;
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

        @media print {
            .screen-only {
                display: none !important;
            }

            html, body {
                margin: 0;
                padding: 0;
                background: #fff;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .facture-container {
                max-width: none;
                width: 100%;
                margin: 0;
                padding: 0;
                border: none;
                box-shadow: none;
            }

            .header,
            .client-info,
            .total-section,
            .signature {
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .articles-table {
                page-break-inside: auto;
            }

            .articles-table tr {
                break-inside: avoid;
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <button type="button" class="screen-only back-btn" onclick="window.location.href='paiement.php'">← Retour au dashboard</button>
    <button type="button" class="screen-only print-btn" onclick="window.print()">🖨️ Imprimer la facture</button>
    
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
                    <td class="text-right"><?php echo format_money((float) $article['prix']); ?></td>
                    <td class="text-right"><?php echo format_money((float) $article['sous_total']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- Totaux -->
        <div class="total-section">
            <div class="total-row">
                <span>Total HT</span>
                <span><?php echo format_money((float) $ht); ?></span>
            </div>
            <div class="total-row">
                <span>TVA (20%)</span>
                <span><?php echo format_money((float) $tva); ?></span>
            </div>
            <div class="total-row total">
                <span>TOTAL TTC</span>
                <span><?php echo format_money((float) $facture['total_paye']); ?></span>
            </div>
        </div>
        
        <!-- Mode de paiement -->
        <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
            <div class="section-title">MODE DE PAIEMENT</div>
            <div style="font-size: 14px;">
                <?php 
                echo htmlspecialchars(dashboard_mode_paiement_label((string) ($facture['mode_paiement'] ?? 'especes')));
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
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('print')) {
            window.print();
        }
    </script>
</body>
</html>