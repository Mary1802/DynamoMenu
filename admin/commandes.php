<?php

require_once __DIR__ . '/../bootstrap/app.php';

use App\Http\AdminPage;
use App\Http\Kernel;
use App\Support\Money;

$result = Kernel::forFile(__FILE__);
if ($result !== null) {
    extract($result, EXTR_SKIP);
}

AdminPage::shellStart('Admin — Commandes', 'commandes', 'Exploitation', 'Commandes', 'Suivi et modification des statuts.');
?>
<?php if ($message): ?><div class="success-message"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>

<div class="chart-container mb-4">
    <div class="chart-title">Rechercher</div>
    <form method="get" class="row g-3">
        <div class="col-md-6">
            <input type="text" name="q" class="form-control" placeholder="Rechercher par numéro de commande, client, ou table..." value="<?php echo htmlspecialchars($q); ?>">
        </div>
        <div class="col-md-4">
            <select name="statut" class="form-select" style="max-width:220px; min-width:220px;">
                <option value="">Tous les statuts</option>
                <?php foreach ($statuts as $k => $label): ?>
                <option value="<?php echo $k; ?>"<?php echo $filter === $k ? ' selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn-primary w-100">Rechercher</button>
        </div>
    </form>
</div>

<div class="chart-container">
    <div class="chart-title">Liste (<?php echo count($commandes); ?>)</div>
    <div class="table-responsive-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>N°</th>
                    <th>Date</th>
                    <th>Client</th>
                    <th>Table</th>
                    <th>Total</th>
                    <th>Statut</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($commandes as $c): ?>
                <tr>
                    <td>#<?php echo str_pad((string) $c['num_commande'], 5, '0', STR_PAD_LEFT); ?></td>
                    <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($c['date_commande']))); ?></td>
                    <td><?php echo htmlspecialchars(trim(($c['prenom_client'] ?? '') . ' ' . ($c['nom_client'] ?? '—'))); ?></td>
                    <td><?php echo (int) $c['num_table']; ?></td>
                    <td><?php echo Money::format((float) $c['montant_total']); ?></td>
                    <td><span class="status-badge"><?php echo htmlspecialchars($statuts[$c['statut']] ?? $c['statut']); ?></span></td>
                    <td>
                        <form method="post" class="d-flex gap-1 flex-wrap" style="align-items:flex-end;">
                            <input type="hidden" name="num_commande" value="<?php echo (int) $c['num_commande']; ?>">
                            <select name="statut" class="form-select form-select-sm" style="width:140px; min-width:140px;">
                                <?php foreach ($statuts as $k => $label): ?>
                                <option value="<?php echo $k; ?>"<?php echo $c['statut'] === $k ? ' selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" name="update_statut" class="btn-details btn-sm" style="width:60px; min-width:60px;">OK</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php AdminPage::shellEnd(); ?>
