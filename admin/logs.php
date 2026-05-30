<?php

require_once __DIR__ . '/../includes/admin_layout.php';

$pdo = admin_pdo();

$logs = [];
try {
    $logs = $pdo->query('SELECT * FROM log_activite ORDER BY date_action DESC LIMIT 200')->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $logs = [];
}

admin_shell_start('Admin — Journaux', 'logs', 'Audit', 'Journaux d\'activité', 'Historique des actions enregistrées.');
?>

<div class="chart-container">
    <div class="chart-title">Derniers événements (<?php echo count($logs); ?>)</div>
    <div class="table-responsive-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Action</th>
                    <th>Module</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($logs === []): ?>
                <tr><td colspan="4" class="text-secondary">Aucun journal pour l'instant. Les actions admin y seront enregistrées.</td></tr>
            <?php else: ?>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?php echo htmlspecialchars(date('d/m/Y H:i:s', strtotime($log['date_action']))); ?></td>
                    <td><?php echo htmlspecialchars($log['action']); ?></td>
                    <td><?php echo htmlspecialchars($log['module_concerne'] ?? '—'); ?></td>
                    <td><?php echo htmlspecialchars($log['description'] ?? ''); ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php admin_shell_end(); ?>
