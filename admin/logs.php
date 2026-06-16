<?php

require_once __DIR__ . '/../bootstrap/app.php';

use App\Http\AdminPage;
use App\Http\Kernel;

$result = Kernel::forFile(__FILE__);
if ($result !== null) {
    extract($result, EXTR_SKIP);
}

AdminPage::shellStart('Admin — Journaux', 'logs', 'Audit', 'Journaux d\'activité', 'Historique des actions enregistrées.');
?>

<div class="chart-container mb-4">
    <div class="chart-title">Rechercher</div>
    <form method="get" class="row g-3">
        <div class="col-md-8">
            <input type="text" name="q" class="form-control" placeholder="Rechercher dans les actions, modules ou descriptions..." value="<?php echo htmlspecialchars($q); ?>">
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn-primary w-100">Rechercher</button>
        </div>
        <?php if ($q !== ''): ?>
        <div class="col-md-2">
            <a href="logs.php" class="btn btn-outline-secondary w-100">Réinitialiser</a>
        </div>
        <?php endif; ?>
    </form>
</div>

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
<?php AdminPage::shellEnd(); ?>
