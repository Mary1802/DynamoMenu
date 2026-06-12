<?php

use App\Core\Application;
use App\Support\PaymentLabels;
use App\View\Staff\OrderDetailView;

$money = Application::getInstance()->moneyFormatter();
?>
<div class="commande-detail-panel-inner">
    <div class="commande-detail-meta">
        <div><span class="text-secondary">N° commande</span><br><strong>#<?php echo str_pad((string) ($commande['num_commande'] ?? ''), 5, '0', STR_PAD_LEFT); ?></strong></div>
        <div><span class="text-secondary">Table</span><br><strong><?php echo htmlspecialchars((string) ($commande['num_table'] ?? '—')); ?></strong></div>
        <div><span class="text-secondary">Statut</span><br><strong><?php echo htmlspecialchars($statut); ?></strong></div>
        <div><span class="text-secondary">Date commande</span><br><strong><?php echo !empty($commande['date_commande']) ? date('d/m/Y H:i', strtotime($commande['date_commande'])) : '—'; ?></strong></div>
    </div>
    <div class="commande-detail-client mt-2">
        <span class="text-secondary">Client</span><br>
        <strong><?php echo htmlspecialchars(trim(($commande['prenom_client'] ?? '') . ' ' . ($commande['nom_client'] ?? '—'))); ?></strong>
        <?php if (!empty($commande['telephone_client'])): ?>
        <br><span class="text-secondary">Tél.</span> <?php echo htmlspecialchars((string) $commande['telephone_client']); ?>
        <?php endif; ?>
    </div>
    <?php if (!empty($commande['num_facture'])): ?>
    <div class="commande-detail-paiement mt-2">
        <div class="settings-panel-title mb-1">Paiement enregistré</div>
        <div class="commande-detail-meta">
            <div><span class="text-secondary">Facture</span><br><strong>#<?php echo str_pad((string) $commande['num_facture'], 4, '0', STR_PAD_LEFT); ?></strong></div>
            <div><span class="text-secondary">Mode</span><br><strong><?php echo htmlspecialchars(PaymentLabels::dashboardMode((string) ($commande['mode_paiement'] ?? 'especes'))); ?></strong></div>
            <div><span class="text-secondary">Payé le</span><br><strong><?php echo !empty($commande['date_paiement']) ? date('d/m/Y H:i', strtotime($commande['date_paiement'])) : '—'; ?></strong></div>
            <div><span class="text-secondary">Montant</span><br><strong><?php echo $money->format((float) ($commande['total_paye'] ?? $commande['montant_total'] ?? 0)); ?></strong></div>
        </div>
        <a href="generer_facture.php?facture=<?php echo (int) $commande['num_facture']; ?>" target="_blank" rel="noopener" class="btn-details btn-sm mt-2 d-inline-block">
            <i class="bi bi-file-earmark-text" aria-hidden="true"></i> Voir facture
        </a>
    </div>
    <?php elseif (!empty($commande['mode_paiement_souhaite'])): ?>
    <div class="commande-detail-paiement mt-2">
        <span class="text-secondary">Paiement souhaité :</span>
        <strong><?php echo $commande['mode_paiement_souhaite'] === 'mobile_money' ? 'Mobile money' : 'Cash'; ?></strong>
    </div>
    <?php endif; ?>
    <?php OrderDetailView::kitchenInstructions($commande['instructions_speciales'] ?? null); ?>
    <div class="mt-3">
        <div class="settings-panel-title mb-2">Articles commandés</div>
        <div class="order-items kitchen-order-items">
            <?php OrderDetailView::kitchenLines($commande['lignes'] ?? []); ?>
        </div>
    </div>
    <div class="commande-detail-total mt-2">
        <span>Total commande</span>
        <strong><?php echo $money->format((float) ($commande['montant_total'] ?? 0)); ?></strong>
    </div>
</div>
