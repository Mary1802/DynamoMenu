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

        .cart-table-header {
            display: none;
        }

        .cart-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .cart-line {
            padding: 1.15rem 1rem;
            background: rgba(255,255,255,0.04);
            border-radius: 18px;
            border-left: 4px solid #ff6f1f;
        }

        .cart-line-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 0.85rem;
        }

        .cart-line-title {
            flex: 1 1 auto;
            min-width: 0;
            margin: 0;
            font-size: 1.05rem;
            font-weight: 700;
            color: #fff;
            line-height: 1.35;
            word-break: break-word;
        }

        .cart-line-total {
            flex: 0 0 auto;
            font-weight: 700;
            font-size: 1.05rem;
            color: #ff6f1f;
            white-space: nowrap;
        }

        .cart-line-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 0.4rem;
            margin-top: 0.65rem;
        }

        .cart-line-badges .badge {
            font-size: 0.78rem;
            font-weight: 500;
            max-width: 100%;
            white-space: normal;
            text-align: left;
            line-height: 1.3;
        }

        .cart-line-footer {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            margin-top: 1rem;
            padding-top: 0.85rem;
            border-top: 1px dashed rgba(255,255,255,0.12);
        }

        .cart-line-unit {
            font-size: 0.88rem;
            color: rgba(229,231,235,0.85);
        }

        .cart-line-unit strong {
            color: #fff;
            font-weight: 600;
        }

        .cart-line-qty {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .cart-line-qty .d-flex {
            background: rgba(255,255,255,0.08);
            padding: 0.35rem 0.65rem;
            border-radius: 14px;
            border: 1px solid rgba(255,255,255,0.12);
        }

        .delete-btn {
            width: 100%;
        }

        .cart-line-delete {
            flex: 1 1 100%;
        }

        .summary-row {
            flex-wrap: wrap;
            gap: 0.35rem 1rem;
        }

        .summary-row span:last-child {
            margin-left: auto;
            text-align: right;
        }

        @media (max-width: 575.98px) {
            .panier-items,
            .panier-summary {
                padding: 1.15rem;
                border-radius: 18px;
            }

            .section-title {
                font-size: 1.35rem;
            }

            .display-5 {
                font-size: 1.75rem;
            }

            .cart-line-footer {
                flex-direction: column;
                align-items: stretch;
                gap: 0.85rem;
            }

            .cart-line-unit {
                text-align: center;
            }

            .cart-line-qty {
                justify-content: center;
            }

            .cart-line-delete .delete-btn {
                display: block;
                width: 100%;
                text-align: center;
            }
        }
    </style>
    <link rel="stylesheet" href="../assets/css/client-luxury.css?v=16">
    <link rel="stylesheet" href="../assets/css/client-pages-theme.css?v=1">
</head>
<body class="client-site client-luxury">
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
            <a href="<?php echo htmlspecialchars(ClientPage::tableLink('menu.php')); ?>" class="btn btn-primary mt-3">
                Voir le menu
            </a>
        </div>
        <?php else: ?>

        <div class="panier-items">
            <h3 class="section-title">Récapitulatif de votre commande</h3>

            <div class="cart-list">
                <?php foreach ($panier as $index => $item): ?>
                <article class="cart-line">
                    <header class="cart-line-header">
                        <h4 class="cart-line-title"><?php echo htmlspecialchars($item['nom']); ?></h4>
                        <span class="cart-line-total"><?php echo Money::format((float) $item['sous_total']); ?></span>
                    </header>

                    <?php if (
                        ($item['type'] === 'plat' && !empty($item['sauces']))
                        || !empty($item['personnalisation'])
                        || isset($item['category'])
                    ): ?>
                    <div class="cart-line-badges">
                        <?php if ($item['type'] === 'plat' && !empty($item['sauces'])): ?>
                        <span class="badge bg-secondary text-white">Sauces : <?php echo htmlspecialchars($item['sauces']); ?></span>
                        <?php elseif (!empty($item['personnalisation'])): ?>
                        <span class="badge bg-secondary text-white"><?php echo htmlspecialchars($item['personnalisation']); ?></span>
                        <?php endif; ?>
                        <?php if (isset($item['category'])): ?>
                        <span class="badge bg-info text-dark"><?php echo htmlspecialchars($item['category']); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <div class="cart-line-footer">
                        <div class="cart-line-unit">
                            <strong><?php echo Money::format((float) $item['prix']); ?></strong>
                            <span class="d-block d-md-none"> / unité</span>
                        </div>

                        <div class="cart-line-qty">
                            <div class="d-flex align-items-center justify-content-center gap-2">
                                <form method="POST" class="d-inline">
                                    <?php ClientPage::csrfField(); ?>
                                    <input type="hidden" name="index" value="<?php echo $index; ?>">
                                    <input type="hidden" name="action" value="minus">
                                    <button type="submit" name="modifier_quantite" class="quantity-btn" aria-label="Diminuer">−</button>
                                </form>
                                <span class="quantity-value"><?php echo (int) $item['quantite']; ?></span>
                                <form method="POST" class="d-inline">
                                    <?php ClientPage::csrfField(); ?>
                                    <input type="hidden" name="index" value="<?php echo $index; ?>">
                                    <input type="hidden" name="action" value="plus">
                                    <button type="submit" name="modifier_quantite" class="quantity-btn" aria-label="Augmenter">+</button>
                                </form>
                            </div>
                        </div>

                        <form method="POST" class="cart-line-delete">
                            <?php ClientPage::csrfField(); ?>
                            <input type="hidden" name="index" value="<?php echo $index; ?>">
                            <button type="submit" name="supprimer_article" class="delete-btn" title="Supprimer cet article">Supprimer</button>
                        </form>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>

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

                <div class="summary-row" style="background: rgba(255,255,255,0.08); border-radius: 10px; padding: 1rem; margin-top: 0.5rem;">
                    <span style="font-size: 1.1rem; font-weight: 700;">Total TTC à payer</span>
                    <span class="panier-total-amount"><?php echo Money::format($total_ttc); ?></span>
                </div>

                <div class="text-center mt-4 pt-3" style="border-top: 1px solid rgba(255,255,255,0.1);">
                    <div class="d-flex flex-column flex-md-row justify-content-center gap-3">
                        <a href="<?php echo htmlspecialchars(ClientPage::tableLink('menu.php')); ?>" class="btn btn-outline-light btn-lg px-4">
                            ← Continuer mes achats
                        </a>
                        <?php if ($tableCtx): ?>
                        <a href="<?php echo htmlspecialchars(ClientPage::tableLink('confirmation.php')); ?>" class="btn btn-primary btn-lg px-4">
                            Confirmer la commande
                        </a>
                        <?php else: ?>
                        <a href="index.php" class="btn btn-warning btn-lg px-4">Retour à l'accueil</a>
                        <?php endif; ?>
                    </div>
                    <p class="text-light mt-3 mb-0 small">
                        <?php if ($tableCtx): ?>
                        Commande pour <strong><?php echo htmlspecialchars($tableCtx['label']); ?></strong>.
                        <?php else: ?>
                        Utilisez l'appareil configuré sur votre table pour commander.
                        <?php endif; ?>
                    </p>
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
                    document.querySelectorAll('[data-cart-count]').forEach(function (cartBadge) {
                        cartBadge.textContent = data.count;
                        cartBadge.style.display = data.count === 0 ? 'none' : 'inline-block';
                    });
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
                    label.style.background = '#c5a059';
                    label.style.color = '#0a0a0a';
                    label.style.borderColor = '#c5a059';
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