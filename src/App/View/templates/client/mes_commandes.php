<?php

declare(strict_types=1);

use App\Http\ClientPage;
use App\Support\Money;
?>
<!DOCTYPE html>

<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Mes commandes — DynamoMenu</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/client-luxury.css?v=18">
    <link rel="stylesheet" href="../assets/css/client-pages-theme.css?v=3">
    <style>
        .order-actions .btn { font-size: 0.85rem; }
        .order-detail-btn {
            white-space: nowrap;
            font-size: 0.8rem;
            padding: 0.3rem 0.65rem;
        }
    </style>
</head>

<body class="client-site client-luxury">

    <?php ClientPage::nav('mes_commandes'); ?>



    <main class="client-orders-wrap">
        <?php if (!empty($orderDetail)):
            $d = $orderDetail;
            $detailStatutClass = match ($d['statut']) {
                'prete' => 'is-ready',
                'livree', 'annulee' => 'is-done',
                default => '',
            };
        ?>
        <div class="client-orders-header">
            <h1>Détail commande</h1>
            <p>Commande #<?php echo str_pad((string) $d['num_commande'], 5, '0', STR_PAD_LEFT); ?></p>
        </div>

        <a href="<?php echo htmlspecialchars($listUrl); ?>" class="btn btn-outline-light btn-sm client-order-detail-back">
            <i class="bi bi-arrow-left"></i> Retour à mes commandes
        </a>

        <article class="order-card client-order-detail-card">
            <span class="order-statut <?php echo $detailStatutClass; ?>"><?php echo htmlspecialchars($d['statut_label']); ?></span>

            <?php if ($d['date_commande'] !== ''): ?>
            <p class="client-order-detail-meta"><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($d['date_commande']))); ?></p>
            <?php endif; ?>

            <div class="client-order-detail-grid">
                <div>
                    <span class="client-order-detail-label">Table</span>
                    <span class="client-order-detail-value"><?php echo htmlspecialchars($d['table_label']); ?></span>
                </div>
                <div>
                    <span class="client-order-detail-label">Client</span>
                    <span class="client-order-detail-value"><?php echo htmlspecialchars($d['client_nom'] !== '' ? $d['client_nom'] : '—'); ?></span>
                </div>
            </div>

            <h2 class="client-order-detail-section">Addition</h2>
            <ul class="client-order-detail-lines">
                <?php foreach ($d['lignes'] as $ligne): ?>
                <li class="client-order-detail-line">
                    <span class="client-order-detail-line-name">
                        <?php echo htmlspecialchars((string) $ligne['nom']); ?>
                        <small>× <?php echo (int) $ligne['quantite']; ?></small>
                    </span>
                    <span class="client-order-detail-line-price"><?php echo Money::format((float) $ligne['sous_total']); ?></span>
                </li>
                <?php endforeach; ?>
            </ul>

            <div class="client-order-detail-total">
                <span>Total TTC</span>
                <span><?php echo Money::format((float) $d['montant_total']); ?></span>
            </div>
        </article>

        <div class="order-actions client-orders-actions">
            <a href="<?php echo htmlspecialchars($listUrl); ?>" class="btn btn-outline-light btn-sm">
                <i class="bi bi-list-ul"></i> Mes commandes
            </a>
            <a href="<?php echo htmlspecialchars($indexUrl); ?>" class="btn btn-outline-light btn-sm">
                <i class="bi bi-house-door"></i> Accueil
            </a>
        </div>

        <?php elseif (($selectedCommande ?? 0) > 0): ?>
        <div class="client-orders-header">
            <h1>Commande introuvable</h1>
            <p>Cette commande n'est pas accessible ou n'existe plus.</p>
        </div>
        <div class="order-actions client-orders-actions">
            <a href="<?php echo htmlspecialchars($listUrl); ?>" class="btn btn-outline-light btn-sm">
                <i class="bi bi-arrow-left"></i> Retour à mes commandes
            </a>
        </div>

        <?php else: ?>
        <div class="client-orders-header">
            <h1>Mes commandes</h1>
            <p>Détail et suivi de vos commandes sur cette table.</p>
        </div>

        <?php if ($orders === []): ?>
        <div class="order-card text-center py-4">
            <i class="bi bi-receipt text-secondary mb-2 d-block" style="font-size:2rem;"></i>
            <p class="text-secondary small mb-3">Aucune commande récente.</p>
            <a href="<?php echo htmlspecialchars($menuUrl); ?>" class="btn btn-primary btn-sm">Voir le menu</a>
        </div>
        <?php else: ?>
        <?php foreach ($orders as $o):
            $statutClass = match ($o['statut']) {
                'prete' => 'is-ready',
                'livree', 'annulee' => 'is-done',
                default => '',
            };
        ?>
        <article class="order-card">
            <div class="d-flex justify-content-between align-items-center gap-2">
                <div class="min-w-0">
                    <div class="text-white fw-semibold" style="font-size:0.95rem;">
                        #<?php echo str_pad((string) $o['num_commande'], 5, '0', STR_PAD_LEFT); ?>
                    </div>
                    <?php if ($o['date_commande'] !== ''): ?>
                    <div class="text-secondary" style="font-size:0.78rem;">
                        <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($o['date_commande']))); ?>
                    </div>
                    <?php endif; ?>
                    <span class="order-statut <?php echo $statutClass; ?> mt-1"><?php echo htmlspecialchars($o['statut_label']); ?></span>
                </div>
                <div class="text-end flex-shrink-0">
                    <div class="text-white fw-bold mb-1" style="font-size:0.95rem;"><?php echo Money::format((float) $o['montant_total']); ?></div>
                    <a href="<?php echo htmlspecialchars($o['detail_url']); ?>" class="btn btn-outline-light order-detail-btn">Détail</a>
                </div>
            </div>
        </article>
        <?php endforeach; ?>
        <?php endif; ?>

        <div class="order-actions client-orders-actions">
            <a href="<?php echo htmlspecialchars($indexUrl); ?>" class="btn btn-outline-light btn-sm">
                <i class="bi bi-house-door"></i> Accueil
            </a>
            <a href="<?php echo htmlspecialchars($menuUrl); ?>" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg"></i> Menu
            </a>
        </div>
        <?php endif; ?>
    </main>

</body>

</html>


