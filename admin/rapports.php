<?php

require_once __DIR__ . '/../includes/admin_layout.php';
require_once __DIR__ . '/../includes/money.php';

$pdo = admin_pdo();
$jour = $_GET['date'] ?? date('Y-m-d');
$mois = $_GET['mois'] ?? date('Y-m');

$rapport_jour = dashboard_sales_totals($pdo, 'day', $jour);
$rapport_mois = dashboard_sales_totals($pdo, 'month', $mois);

$stmt = $pdo->prepare("
    SELECT f.*, c.num_table, cl.nom_client, cl.prenom_client
    FROM facture f
    JOIN commande c ON f.num_commande = c.num_commande
    LEFT JOIN client cl ON c.id_client = cl.id_client
    WHERE DATE(f.date_facture) = ?
    ORDER BY f.date_facture DESC
");
$stmt->execute([$jour]);
$lignes_jour = $stmt->fetchAll(PDO::FETCH_ASSOC);

admin_shell_start('Admin — Rapports ventes', 'rapports', 'Administration', 'Rapports journalier & mensuel', 'Ventilation cash / mobile money pour éviter les confusions.');
?>
<div class="stats-row mb-4">
    <div class="stat-box dashboard-card">
        <div class="stat-value"><?php echo format_money((float) $rapport_jour['ca']); ?></div>
        <div class="stat-label">CA jour</div>
        <div class="payment-split-row">
            <div class="payment-split-box"><div class="label">Cash</div><div class="value"><?php echo format_money((float) $rapport_jour['ca_especes']); ?></div></div>
            <div class="payment-split-box"><div class="label">Mobile money</div><div class="value"><?php echo format_money((float) $rapport_jour['ca_mobile']); ?></div></div>
        </div>
    </div>
    <div class="stat-box dashboard-card">
        <div class="stat-value"><?php echo format_money((float) $rapport_mois['ca']); ?></div>
        <div class="stat-label">CA mois <?php echo htmlspecialchars($mois); ?></div>
        <div class="payment-split-row">
            <div class="payment-split-box"><div class="label">Cash</div><div class="value"><?php echo format_money((float) $rapport_mois['ca_especes']); ?></div></div>
            <div class="payment-split-box"><div class="label">Mobile money</div><div class="value"><?php echo format_money((float) $rapport_mois['ca_mobile']); ?></div></div>
        </div>
    </div>
</div>

<form class="chart-container mb-4 row g-3" method="get">
    <div class="col-md-4">
        <label class="form-label text-secondary">Jour</label>
        <input type="date" name="date" class="form-control" value="<?php echo htmlspecialchars($jour); ?>">
    </div>
    <div class="col-md-4">
        <label class="form-label text-secondary">Mois</label>
        <input type="month" name="mois" class="form-control" value="<?php echo htmlspecialchars($mois); ?>">
    </div>
    <div class="col-md-4 d-flex align-items-end">
        <button type="submit" class="btn-primary w-100">Actualiser</button>
    </div>
</form>

<div class="chart-container">
    <div class="chart-title">Détail des factures — <?php echo htmlspecialchars($jour); ?></div>
    <div class="table-responsive-wrap">
        <table class="data-table">
            <thead>
                <tr><th>Facture</th><th>Commande</th><th>Client</th><th>Table</th><th>Mode</th><th>Montant</th><th>Heure</th></tr>
            </thead>
            <tbody>
            <?php if (empty($lignes_jour)): ?>
                <tr><td colspan="7">Aucune vente ce jour</td></tr>
            <?php else: foreach ($lignes_jour as $l): ?>
                <tr>
                    <td>#<?php echo (int) $l['num_facture']; ?></td>
                    <td>#<?php echo str_pad((string) $l['num_commande'], 5, '0', STR_PAD_LEFT); ?></td>
                    <td><?php echo htmlspecialchars(trim(($l['prenom_client'] ?? '') . ' ' . ($l['nom_client'] ?? ''))); ?></td>
                    <td><?php echo htmlspecialchars((string) $l['num_table']); ?></td>
                    <td><?php echo htmlspecialchars(dashboard_mode_paiement_label((string) $l['mode_paiement'])); ?></td>
                    <td><?php echo format_money((float) $l['total_paye']); ?></td>
                    <td><?php echo date('H:i', strtotime($l['date_facture'])); ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php admin_shell_end(); ?>
