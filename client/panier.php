<?php
require_once __DIR__ . '/../bootstrap/app.php';

use App\Http\ClientPage;
use App\Http\Kernel;
use App\Support\Money;

$result = Kernel::forFile(__FILE__);
if ($result !== null) {
    extract($result, EXTR_SKIP);
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mon Panier - DynamoMenu</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/style.css?v=7">
    <?php ClientPage::csrfMetaTag(); ?>
    <style>
        body {
            background: radial-gradient(circle at top left, rgba(255,111,31,0.16), transparent 28%),
                        linear-gradient(180deg, #071119 0%, #0b1521 40%, #0f172a 100%);
            color: #f8fafc;
        }

        .panier-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2.5rem 1rem 3rem;
        }
        
        .section-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 1.75rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid rgba(255,111,31,0.95);
        }
        
        .panier-items {
            background: rgba(7, 7, 12, 0.88);
            border: 1px solid rgba(255,111,31,0.18);
            border-radius: 24px;
            box-shadow: 0 20px 70px rgba(0,0,0,0.35);
            padding: 2rem;
            margin-bottom: 2rem;
            backdrop-filter: blur(14px);
        }
        
        .panier-item {
            padding: 1.5rem 1.25rem;
            background: rgba(255,255,255,0.04);
            border-radius: 18px;
            margin-bottom: 1rem;
            border-left: 4px solid #ff6f1f;
            color: #e5e7eb;
        }
        
        .item-info {
            padding-right: 1rem;
        }
        
        .item-name {
            font-weight: 700;
            color: #fff;
            margin-bottom: 0.5rem;
            font-size: 1.15rem;
        }
        
        .item-details {
            font-size: 0.95rem;
            color: rgba(229,231,235,0.8);
            margin-top: 0.75rem;
        }
        
        .item-details small {
            display: block;
            margin-bottom: 0.35rem;
            padding: 0.25rem 0.55rem;
            background: rgba(255,255,255,0.08);
            border-radius: 999px;
            display: inline-block;
            color: #e5e7eb;
        }
        
        .item-details .badge {
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
            background: rgba(255,255,255,0.08);
            color: #fff;
        }
        
        .item-quantity {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
        }
        
        .item-quantity .d-flex {
            background: rgba(255,255,255,0.08);
            padding: 0.5rem 1rem;
            border-radius: 14px;
            border: 1px solid rgba(255,255,255,0.12);
        }
        
        .quantity-btn {
            width: 36px;
            height: 36px;
            border: 1px solid rgba(255,255,255,0.18);
            background: transparent;
            color: #fff;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-weight: bold;
            font-size: 1.2rem;
            transition: all 0.2s ease;
        }
        
        .quantity-btn:hover {
            background: #ff6f1f;
            color: #111;
            border-color: #ff6f1f;
            transform: scale(1.05);
        }
        
        .quantity-value {
            min-width: 40px;
            text-align: center;
            font-weight: 700;
            font-size: 1.2rem;
            color: #fff;
        }
        
        .item-price {
            font-weight: 700;
            color: #ff6f1f;
            text-align: center;
            padding: 0.85rem;
            background: rgba(255,255,255,0.05);
            border-radius: 14px;
            border: 1px solid rgba(255,255,255,0.12);
        }
        
        .delete-btn {
            background: rgba(220,53,69,0.12);
            border: 1px solid rgba(220,53,69,0.3);
            color: #ffb3b3;
            cursor: pointer;
            padding: 0.55rem 1rem;
            border-radius: 10px;
            font-size: 0.95rem;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        
        .delete-btn:hover {
            background: rgba(220,53,69,0.2);
            border-color: #d32f2f;
            transform: translateY(-2px);
            box-shadow: 0 3px 12px rgba(211, 47, 47, 0.2);
        }
        
        .panier-summary {
            background: rgba(15, 23, 42, 0.95);
            border-radius: 24px;
            box-shadow: 0 20px 70px rgba(0,0,0,0.35);
            padding: 2rem;
            border: 1px solid rgba(255,111,31,0.25);
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 1rem 0;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            font-size: 1rem;
            color: #e5e7eb;
        }
        
        .summary-row:last-child {
            border-bottom: none;
        }
        
        .btn-confirm {
            background: linear-gradient(135deg, #ff6f1f, #ff8a3d);
            border: none;
            border-radius: 12px;
            color: #111;
            padding: 1rem 2rem;
            font-weight: 700;
            font-size: 1rem;
            width: 100%;
            margin-top: 1.5rem;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 14px 30px rgba(255,111,31,0.25);
        }
        
        .btn-confirm:hover {
            background: linear-gradient(135deg, #ff8a3d, #ff6f1f);
            transform: translateY(-2px);
            box-shadow: 0 18px 35px rgba(255,111,31,0.35);
        }
        
        .empty-panier {
            text-align: center;
            padding: 3rem;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 24px;
            color: #e5e7eb;
        }
        
        .empty-icon {
            font-size: 4rem;
            opacity: 0.3;
            margin-bottom: 1rem;
        }
        
        .empty-panier h3 {
            color: #fff;
        }
        
        .empty-panier p {
            color: rgba(229,231,235,0.8);
        }
        
        .empty-panier .btn-primary {
            background: #ff6f1f;
            border-color: #ff6f1f;
        }
        
        .sauces-options {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        
        .sauce-checkbox {
            display: none;
        }
        
        .sauce-label {
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 20px;
            padding: 0.25rem 0.75rem;
            font-size: 0.85rem;
            color: #e5e7eb;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .sauce-checkbox:checked + .sauce-label {
            background: #ff6f1f;
            color: #111;
            border-color: #ff6f1f;
        }
        
        .personnalisation-select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 12px;
            margin-top: 0.5rem;
            background: rgba(255,255,255,0.05);
            color: #fff;
        }
    </style>
</head>
<body class="client-site">
    <?php ClientPage::nav('panier'); ?>

    <main class="container-fluid px-3 px-md-4 py-4 py-md-5">
        <div class="row">
            <div class="col-lg-12">
                <div class="text-center mb-5">
                    <p class="text-uppercase text-light mb-2">Votre commande</p>
                    <h1 class="display-5 fw-bold">Votre <span class="text-warning">panier</span></h1>
                    <p class="lead text-light">Vérifiez et confirmez votre commande</p>
                </div>
        
        <?php if (empty($panier)): ?>
        <div class="empty-panier">
            <h3>Votre panier est vide</h3>
            <p>Ajoutez des plats et boissons pour commencer votre commande</p>
            <a href="<?php echo htmlspecialchars(ClientPage::tableLink('menu.php')); ?>" class="btn btn-primary mt-3" style="background: #ff6f1f; border-color: #ff6f1f;">
                Voir le menu
            </a>
        </div>
        <?php else: ?>
        
        <div class="row">
            <div class="col-lg-12">
                <div class="panier-items">
                    <h3 class="section-title">Récapitulatif de votre commande</h3>
                    
                    <!-- En-tête des colonnes -->
                    <div class="row mb-3" style="background: rgba(255,255,255,0.06); padding: 0.75rem; border-radius: 12px; font-weight: 600; color: rgba(226,232,240,0.8); font-size: 0.95rem;">
                        <div class="col-6">Article & Détails</div>
                        <div class="col-2 text-center">Prix unitaire</div>
                        <div class="col-2 text-center">Quantité</div>
                        <div class="col-2 text-center">Total article</div>
                    </div>
                    
                    <?php foreach ($panier as $index => $item): ?>
                    <div class="panier-item row align-items-center">
                        <div class="item-info col-6">
                            <div class="item-name">
                                <strong style="font-size: 1.1rem;"><?php echo htmlspecialchars($item['nom']); ?></strong>
                                <div class="item-details mt-2">
                                    <?php if ($item['type'] === 'plat' && !empty($item['sauces'])): ?>
                                    <div class="mb-1"><span class="badge bg-secondary text-white" style="font-size: 0.8rem;">Sauces: <?php echo htmlspecialchars($item['sauces']); ?></span></div>
                                    <?php elseif (!empty($item['personnalisation'])): ?>
                                    <div class="mb-1"><span class="badge bg-secondary text-white" style="font-size: 0.8rem;"><?php echo htmlspecialchars($item['personnalisation']); ?></span></div>
                                    <?php endif; ?>
                                    <?php if (isset($item['category'])): ?>
                                    <div><span class="badge bg-info" style="font-size: 0.8rem;"><?php echo htmlspecialchars($item['category']); ?></span></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-2 text-center">
                            <div style="font-weight: 600; color: #fff; font-size: 1.1rem;"><?php echo Money::format((float) $item['prix']); ?></div>
                            <div style="font-size: 0.8rem; color: rgba(229,231,235,0.85);">Prix unitaire</div>
                        </div>
                        
                        <div class="item-quantity col-2 text-center">
                            <div class="d-flex align-items-center justify-content-center gap-2">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="index" value="<?php echo $index; ?>">
                                    <input type="hidden" name="action" value="minus">
                                    <button type="submit" name="modifier_quantite" class="quantity-btn">-</button>
                                </form>
                                
                                <span class="quantity-value"><?php echo $item['quantite']; ?></span>
                                
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="index" value="<?php echo $index; ?>">
                                    <input type="hidden" name="action" value="plus">
                                    <button type="submit" name="modifier_quantite" class="quantity-btn">+</button>
                                </form>
                            </div>
                            <div style="font-size: 0.8rem; color: rgba(229,231,235,0.85); margin-top: 0.25rem;">Quantité</div>
                        </div>
                    </div>
                    
                    <!-- Ligne de séparation et actions -->
                    <div class="row mt-2 mb-4">
                        <div class="col-10">
                            <div style="border-top: 1px dashed rgba(255,255,255,0.12); padding-top: 0.5rem; font-size: 0.9rem; color: rgba(229,231,235,0.75);">
                                <strong>Calcul :</strong> <?php echo Money::format((float) $item['prix']); ?> × <?php echo $item['quantite']; ?> = <?php echo Money::format((float) $item['sous_total']); ?>
                            </div>
                        </div>
                        <div class="col-2 text-center">
                            <form method="POST">
                                <input type="hidden" name="index" value="<?php echo $index; ?>">
                                <button type="submit" name="supprimer_article" class="delete-btn" title="Supprimer cet article">Supprimer</button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <!-- Récapitulatif total -->
                    <div class="panier-summary mt-4">
                        <h3 class="section-title">Total de la commande</h3>
                        
                        <div class="summary-row">
                            <span>Nombre total d'articles</span>
                            <span><strong><?php echo $nombre_articles; ?> article(s)</strong></span>
                        </div>
                        
                        <div class="summary-row">
                            <span>Sous-total des articles</span>
                            <span><?php echo Money::format($total_panier); ?></span>
                        </div>
                        
                        <div class="summary-row">
                            <span>TVA (16%)</span>
                            <span><?php echo Money::format($tva_amount); ?></span>
                        </div>
                        
                        <div class="summary-row" style="background: rgba(255,255,255,0.08); border-radius: 6px; padding: 1rem; color: #e5e7eb;">
                            <span style="font-size: 1.2rem; font-weight: 700;">Total TTC à payer</span>
                            <span style="font-size: 1.4rem; font-weight: 700; color: #ff6f1f;"><?php echo Money::format($total_ttc); ?></span>
                        </div>
                        
                        <div class="text-center mt-5 pt-4" style="border-top: 1px solid #eee;">
                            <div class="d-flex flex-column flex-md-row justify-content-center gap-3">
                                <a href="<?php echo htmlspecialchars(ClientPage::tableLink('menu.php')); ?>" class="btn btn-outline-light btn-lg px-4 px-md-5">
                                    ← Continuer mes achats
                                </a>
                                <?php if ($tableCtx): ?>
                                <a href="<?php echo htmlspecialchars(ClientPage::tableLink('confirmation.php')); ?>" class="btn btn-primary btn-lg px-4 px-md-5" style="background: #ff6f1f; border-color: #ff6f1f;">
                                    Confirmer la commande
                                </a>
                                <?php else: ?>
                                <a href="index.php?err=table" class="btn btn-warning btn-lg px-4 px-md-5">Scanner le QR de la table</a>
                                <?php endif; ?>
                            </div>
                            <p class="text-light mt-3 mb-0 small">
                                <?php if ($tableCtx): ?>
                                Commande pour <strong><?php echo htmlspecialchars($tableCtx['label']); ?></strong> — pas de nouveau scan nécessaire.
                                <?php else: ?>
                                Un scan QR sur votre table suffit pour toute la visite.
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php ClientPage::footer(); ?>

    <script src="../assets/js/csrf.js?v=1"></script>
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mettre à jour le badge du panier
        function updateCartBadge() {
            fetch('get_cart_count.php')
                .then(response => response.json())
                .then(data => {
                    const cartBadge = document.getElementById('cartCount');
                    if (cartBadge) {
                        cartBadge.textContent = data.count;
                        // Cacher le badge si 0
                        if (data.count === 0) {
                            cartBadge.style.display = 'none';
                        } else {
                            cartBadge.style.display = 'block';
                        }
                    }
                });
        }
        
        // Mettre à jour au chargement
        document.addEventListener('DOMContentLoaded', updateCartBadge);
        
        // Vérifier le panier toutes les 5 secondes
        setInterval(updateCartBadge, 5000);
        
        // Gestion des sauces
        document.querySelectorAll('.sauce-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const label = this.nextElementSibling;
                if (this.checked) {
                    label.style.background = '#ff6f1f';
                    label.style.color = '#111';
                    label.style.borderColor = '#ff6f1f';
                } else {
                    label.style.background = 'rgba(255,255,255,0.08)';
                    label.style.color = '#e5e7eb';
                    label.style.borderColor = 'rgba(255,255,255,0.12)';
                }
            });
        });
    </script>
</body>
</html>