<?php $numTable = (int) ($tableCtx['num_table'] ?? 0); ?>
<div class="client-table-welcome mb-4" role="status" aria-live="polite">
    <div class="client-table-welcome__accent" aria-hidden="true"></div>
    <div class="client-table-welcome__inner">
        <div class="client-table-welcome__icon" aria-hidden="true">
            <i class="bi bi-check2-circle"></i>
        </div>
        <div class="client-table-welcome__content">
            <p class="client-table-welcome__eyebrow">Bienvenue</p>
            <p class="client-table-welcome__title"><?php echo htmlspecialchars((string) ($tableCtx['label'] ?? ('Table ' . $numTable))); ?></p>
            <p class="client-table-welcome__text">Bon appétit — commandez depuis le menu, nous préparons votre commande pour cette table.</p>
        </div>
        <div class="client-table-welcome__table-pill" aria-label="Numéro de table <?php echo $numTable; ?>">
            <span class="client-table-welcome__table-pill-label">Table</span>
            <span class="client-table-welcome__table-pill-num"><?php echo $numTable > 0 ? $numTable : '—'; ?></span>
        </div>
    </div>
</div>
