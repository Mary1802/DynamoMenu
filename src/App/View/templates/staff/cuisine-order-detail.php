<?php

use App\Core\Application;
use App\View\Staff\OrderDetailView;

$money = Application::getInstance()->moneyFormatter();
?>
<div class="commande-detail-panel-inner">
    <div class="commande-detail-meta">
        <div><span class="text-secondary">N° commande</span><br><strong>#<?php echo str_pad((string) ($commande['num_commande'] ?? ''), 5, '0', STR_PAD_LEFT); ?></strong></div>
        <div><span class="text-secondary">Table</span><br><strong><?php echo htmlspecialchars((string) ($commande['num_table'] ?? '—')); ?></strong></div>
        <div><span class="text-secondary">Statut</span><br><strong><?php echo htmlspecialchars($statut); ?></strong></div>
        <div><span class="text-secondary">Date</span><br><strong><?php echo !empty($commande['date_commande']) ? date('d/m/Y H:i', strtotime($commande['date_commande'])) : '—'; ?></strong></div>
    </div>
    <div class="commande-detail-client mt-2">
        <span class="text-secondary">Client</span><br>
        <strong><?php echo htmlspecialchars(trim(($commande['prenom_client'] ?? '') . ' ' . ($commande['nom_client'] ?? '—'))); ?></strong>
        <?php if (!empty($commande['telephone_client'])): ?>
        <br><span class="text-secondary">Tél.</span> <?php echo htmlspecialchars((string) $commande['telephone_client']); ?>
        <?php endif; ?>
    </div>
    <?php OrderDetailView::kitchenInstructions($commande['instructions_speciales'] ?? null); ?>
    <div class="mt-3">
        <div class="settings-panel-title mb-2">Articles à préparer</div>
        <div class="order-items kitchen-order-items">
            <?php OrderDetailView::kitchenLines($commande['lignes'] ?? []); ?>
        </div>
    </div>
    <?php if (isset($commande['montant_total'])): ?>
    <div class="commande-detail-total mt-2">
        <span>Total</span>
        <strong><?php echo $money->format((float) $commande['montant_total']); ?></strong>
    </div>
    <?php endif; ?>
</div>
