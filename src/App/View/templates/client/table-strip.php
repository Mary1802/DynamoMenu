<?php $numTable = (int) ($tableCtx['num_table'] ?? 0); ?>
<div class="client-table-strip d-lg-none" role="status">
    <span class="client-table-strip__pill">
        <i class="bi bi-check2-circle" aria-hidden="true"></i>
        <span class="client-table-strip__label">Table <?php echo $numTable > 0 ? $numTable : htmlspecialchars((string) $tableCtx['label']); ?></span>
    </span>
    <span class="client-table-strip__msg">Bon appétit</span>
</div>
