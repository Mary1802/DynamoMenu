<?php $panelId = 'notifPanel'; ?>
<div class="notification-wrap">
    <button type="button" class="notification-btn" id="notifToggle" aria-expanded="false" aria-controls="<?php echo $panelId; ?>" aria-label="Notifications">
        <i class="bi bi-bell" aria-hidden="true"></i>
        <?php if ($badgeCount > 0): ?>
        <span class="notification-badge"><?php echo (int) $badgeCount; ?></span>
        <?php endif; ?>
    </button>
    <div class="notification-panel" id="<?php echo $panelId; ?>" hidden>
        <div class="notification-panel-header">Notifications</div>
        <?php if ($items === []): ?>
            <p class="notification-panel-empty">Aucune alerte pour le moment.</p>
        <?php else: ?>
            <ul class="notification-panel-list">
                <?php foreach ($items as $item): ?>
                <li>
                    <?php if ($role === 'cuisinier'): ?>
                    <a href="dashboard.php#cmd-<?php echo (int) $item['num_commande']; ?>">
                        #<?php echo str_pad((string) $item['num_commande'], 5, '0', STR_PAD_LEFT); ?>
                        — Table <?php echo htmlspecialchars((string) ($item['num_table'] ?? '—')); ?>
                        <span class="notification-panel-kicker">Nouvelle commande</span>
                    </a>
                    <?php elseif ($role === 'manager'): ?>
                    <a href="<?php echo htmlspecialchars((string) ($item['href'] ?? 'dashboard.php')); ?>">
                        <?php echo htmlspecialchars((string) ($item['label'] ?? 'Commande prête')); ?>
                        <?php if (!empty($item['nom_client'])): ?>
                        <span class="notification-panel-kicker"><?php echo htmlspecialchars((string) $item['nom_client']); ?></span>
                        <?php endif; ?>
                    </a>
                    <?php else: ?>
                    <a href="<?php echo htmlspecialchars($item['href']); ?>">
                        <?php echo htmlspecialchars($item['label']); ?>
                    </a>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
