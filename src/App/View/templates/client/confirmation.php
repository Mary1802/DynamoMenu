<?php

declare(strict_types=1);

use App\Http\ClientPage;
use App\Support\Money;
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Confirmation de Commande - DynamoMenu</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <?php ClientPage::csrfMetaTag(); ?>
    <style>
        body {
            background: radial-gradient(circle at top left, rgba(255,111,31,0.14), transparent 22%),
                        linear-gradient(180deg, #071119 0%, #0f172a 50%, #111827 100%);
            color: #f8fafc;
        }

        .confirmation-container {
            max-width: 1120px;
            margin: 0 auto;
            padding: 3rem 1rem 4rem;
        }
        
        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #f8fafc;
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid #ff6f1f;
        }
        
        .confirmation-card {
            background: rgba(15, 23, 42, 0.94);
            border-radius: 24px;
            border: 1px solid rgba(255,111,31,0.15);
            box-shadow: 0 24px 80px rgba(0,0,0,0.35);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            font-weight: 600;
            color: #e2e8f0;
            margin-bottom: 0.5rem;
            display: block;
        }
        
        .form-control,
        .form-control:focus,
        .form-control:disabled {
            width: 100%;
            padding: 0.85rem 1rem;
            border: 1px solid rgba(148,163,184,0.24);
            border-radius: 14px;
            font-size: 1rem;
            background: rgba(15, 23, 42, 0.8);
            color: #e2e8f0;
            box-shadow: none;
        }
        
        .form-control::placeholder {
            color: rgba(226,232,240,0.6);
        }
        
        .form-control:focus {
            border-color: #ff6f1f;
            box-shadow: 0 0 0 4px rgba(255,111,31,0.12);
            outline: none;
        }
        
        .required::after {
            content: ' *';
            color: #f87171;
        }
        
        .recap-panier {
            background: rgba(15, 23, 42, 0.9);
            border-radius: 24px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border: 1px solid rgba(148,163,184,0.16);
        }
        
        .recap-item {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
            padding: 0.85rem 0;
            border-bottom: 1px solid rgba(148,163,184,0.12);
            color: #cbd5e1;
        }

        .recap-item-main {
            flex: 1 1 auto;
            min-width: 0;
        }

        .recap-item-main strong {
            display: block;
            color: #f8fafc;
            line-height: 1.35;
            word-break: break-word;
        }

        .recap-item-detail {
            font-size: 0.85rem;
            color: rgba(203,213,225,0.85);
            margin-top: 0.35rem;
            line-height: 1.4;
            word-break: break-word;
        }

        .recap-item-price {
            flex: 0 0 auto;
            font-weight: 600;
            color: #ff6f1f;
            white-space: nowrap;
            text-align: right;
        }
        
        .recap-item:last-child {
            border-bottom: none;
        }
        
        .recap-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.5rem;
            padding: 1rem 0;
            border-top: 2px solid rgba(255,111,31,0.24);
            font-weight: 700;
            font-size: 1.15rem;
            color: #f8fafc;
        }

        .recap-total span:last-child {
            color: #ff6f1f;
            font-size: 1.2rem;
        }

        .confirmation-hero h1 {
            color: #f8fafc;
            font-size: clamp(1.5rem, 5vw, 2.75rem);
            font-weight: 800;
            line-height: 1.2;
        }

        .confirmation-hero p {
            color: rgba(226,232,240,0.82);
            font-size: 1rem;
            line-height: 1.6;
        }

        .client-info-box p {
            margin-bottom: 0.35rem;
            word-break: break-word;
        }

        @media (max-width: 767.98px) {
            .confirmation-container {
                padding: 1.5rem 0.85rem 2.5rem;
            }

            .confirmation-card {
                padding: 1.15rem;
                border-radius: 18px;
            }

            .recap-panier {
                padding: 1rem;
            }

            .confirmation-nav .btn {
                font-size: 0.88rem;
                padding: 0.45rem 0.75rem;
            }

            .confirmation-hero {
                padding: 1.15rem !important;
            }

            .section-title {
                font-size: 1.25rem;
            }
        }
        
        .btn-confirm {
            background: linear-gradient(135deg, #ff6f1f, #ff8a3d);
            border: none;
            border-radius: 14px;
            color: white;
            padding: 1rem 2rem;
            font-weight: 700;
            font-size: 1rem;
            width: 100%;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .btn-confirm:hover {
            background: linear-gradient(135deg, #ff8a3d, #ff6f1f);
            transform: translateY(-2px);
            box-shadow: 0 18px 45px rgba(255,111,31,0.28);
        }

        .confirmation-actions {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            margin-top: 0.25rem;
        }

        .btn-cancel-order {
            background: transparent;
            border: 1px solid rgba(248, 113, 113, 0.55);
            border-radius: 14px;
            color: #fca5a5;
            padding: 0.9rem 1.5rem;
            font-weight: 600;
            font-size: 0.95rem;
            width: 100%;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-cancel-order:hover {
            background: rgba(220, 53, 69, 0.14);
            border-color: #f87171;
            color: #fecaca;
        }
        
        .error-message {
            background: rgba(248,215,218,0.18);
            color: #f8d7da;
            padding: 1rem;
            border-radius: 14px;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(248,215,218,0.35);
        }
        
        .table-select {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(90px, 1fr));
            gap: 0.75rem;
            margin-top: 0.5rem;
        }
        
        .table-option {
            display: none;
        }
        
        .table-label {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            border: 2px solid rgba(148,163,184,0.24);
            border-radius: 18px;
            cursor: pointer;
            transition: all 0.2s ease;
            text-align: center;
            background: rgba(15, 23, 42, 0.75);
            color: #cbd5e1;
        }
        
        .table-option:checked + .table-label {
            border-color: #ff6f1f;
            background: rgba(255, 111, 31, 0.14);
            color: #f8fafc;
        }
        
        .table-number {
            font-size: 1.6rem;
            font-weight: 700;
            color: inherit;
        }
        
        .table-places {
            font-size: 0.9rem;
            color: rgba(226,232,240,0.72);
        }
    </style>
    <link rel="stylesheet" href="../assets/css/client-luxury.css?v=16">
    <link rel="stylesheet" href="../assets/css/client-pages-theme.css?v=1">
</head>
<body class="client-site client-luxury">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-transparent position-relative py-3 confirmation-nav">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <span class="client-brand-accent">Dynamo</span><span class="client-brand-name">Menu</span>
            </a>
            <div class="d-flex align-items-center">
                <a href="panier.php" class="btn btn-outline-light">
                    <span class="d-none d-sm-inline">← Retour au panier</span>
                    <span class="d-sm-none">← Panier</span>
                </a>
            </div>
        </div>
    </nav>

    <div class="confirmation-container">
        <div class="mb-4 p-4 rounded-4 confirmation-hero">
            <h1 class="mb-2">Confirmation de commande</h1>
            <p class="mb-0">
                Vérifiez votre commande, choisissez le mode de paiement et envoyez-la à la cuisine.
            </p>
        </div>
        
        <?php if ($error !== null): ?>
        <div class="error-message">
            ❌ <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-lg-8">
                <div class="confirmation-card">
                    <h3 class="section-title">Vos informations</h3>
                    <div class="mb-4 p-3 rounded-3 client-info-box" style="background:rgba(255,255,255,0.04);border:1px solid rgba(148,163,184,0.16);">
                        <p class="mb-2"><strong><?php echo htmlspecialchars(trim($clientProfile['prenom'] . ' ' . $clientProfile['nom'])); ?></strong></p>
                        <?php if (!empty($clientProfile['fidele'])): ?>
                        <p class="mb-1 text-secondary small"><i class="bi bi-star-fill text-warning" aria-hidden="true"></i> Client fidèle</p>
                        <?php endif; ?>
                        <?php if (trim((string) ($clientProfile['telephone'] ?? '')) !== ''): ?>
                        <p class="mb-1 text-secondary small"><?php echo htmlspecialchars($clientProfile['telephone']); ?></p>
                        <?php endif; ?>
                        <?php if (trim((string) ($clientProfile['email'] ?? '')) !== ''): ?>
                        <p class="mb-1 text-secondary small"><?php echo htmlspecialchars($clientProfile['email']); ?></p>
                        <?php endif; ?>
                        <p class="mb-0 text-secondary small">Table : <?php echo htmlspecialchars($tableCtx['label']); ?></p>
                    </div>

                    <h3 class="section-title">Finaliser la commande</h3>
                    
                    <form method="POST">
                        <?php ClientPage::csrfField(); ?>
                        <input type="hidden" name="num_table" value="<?php echo (int) $tableCtx['num_table']; ?>">

                        <div class="form-group">
                            <label class="form-label required">Mode de paiement prévu</label>
                            <div class="d-flex gap-3 flex-wrap">
                                <label class="d-flex align-items-center gap-2">
                                    <input type="radio" name="mode_paiement_souhaite" value="especes" required
                                        <?php echo (($_POST['mode_paiement_souhaite'] ?? '') === 'especes') ? 'checked' : ''; ?>>
                                    Cash
                                </label>
                                <label class="d-flex align-items-center gap-2">
                                    <input type="radio" name="mode_paiement_souhaite" value="mobile_money" required
                                        <?php echo (($_POST['mode_paiement_souhaite'] ?? '') === 'mobile_money') ? 'checked' : ''; ?>>
                                    Mobile money
                                </label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Instructions spéciales (optionnel)</label>
                            <textarea name="instructions" class="form-control" rows="3" 
                                      placeholder="Allergies, préférences alimentaires, etc."></textarea>
                        </div>
                        
                        <div class="confirmation-actions">
                            <button type="submit" name="confirmer_commande" class="btn-confirm">
                                Confirmer et envoyer la commande à la cuisine
                            </button>
                        </div>
                    </form>
                    <form method="POST" class="mt-3" onsubmit="return confirm('Annuler cette commande ? Vos informations personnelles et le panier seront effacés.');">
                        <?php ClientPage::csrfField(); ?>
                        <button type="submit" name="annuler_commande" class="btn-cancel-order">
                            Annuler la commande
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="confirmation-card">
                    <h3 class="section-title">Récapitulatif</h3>
                    
                    <div class="recap-panier">
                        <?php foreach ($panier as $item): ?>
                        <div class="recap-item">
                            <div class="recap-item-main">
                                <strong><?php echo htmlspecialchars($item['nom']); ?></strong>
                                <div class="recap-item-detail">
                                    Quantité : <?php echo (int) $item['quantite']; ?>
                                    <?php if ($item['type'] === 'plat' && !empty($item['sauces'])): ?>
                                    <br>Sauces : <?php echo htmlspecialchars($item['sauces']); ?>
                                    <?php elseif (!empty($item['personnalisation'])): ?>
                                    <br><?php echo htmlspecialchars($item['personnalisation']); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="recap-item-price"><?php echo Money::format((float) $item['sous_total']); ?></div>
                        </div>
                        <?php endforeach; ?>
                        
                        <div class="recap-item">
                            <div class="recap-item-main">Sous-total</div>
                            <div class="recap-item-price"><?php echo Money::format($total_panier); ?></div>
                        </div>
                        
                        <div class="recap-item">
                            <div class="recap-item-main">TVA (16%)</div>
                            <div class="recap-item-price"><?php echo Money::format($tva_amount); ?></div>
                        </div>
                        <div class="recap-total">
                            <div>Total TTC</div>
                            <div id="recapTotal"><?php echo Money::format($total_ttc); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/csrf.js?v=1"></script>
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script>
        // Gestion de la sélection des tables
        document.querySelectorAll('.table-option').forEach(option => {
            option.addEventListener('change', function() {
                document.querySelectorAll('.table-label').forEach(label => {
                    label.style.borderColor = '#ddd';
                    label.style.background = 'white';
                });
                
                if (this.checked) {
                    const label = this.nextElementSibling;
                    label.style.borderColor = '#c5a059';
                    label.style.background = 'rgba(197, 160, 89, 0.14)';
                }
            });
        });
        
        const firstTable = document.querySelector('.table-option');
        if (firstTable) {
            firstTable.checked = true;
            firstTable.dispatchEvent(new Event('change'));
        }
    </script>
</body>
</html>
