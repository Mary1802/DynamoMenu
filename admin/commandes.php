<?php

require_once __DIR__ . '/../includes/admin_layout.php';
require_once __DIR__ . '/../includes/schema_upgrade.php';
require_once __DIR__ . '/../includes/money.php';

$pdo = admin_init();
schema_upgrade($pdo);

$message = '';
$statuts = [
    'en_attente' => 'En attente',
    'en_preparation' => 'En préparation',
    'prete' => 'Prête',
    'livree' => 'Livrée',
    'annulee' => 'Annulée',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_statut'])) {
    $num = (int) $_POST['num_commande'];
    $statut = $_POST['statut'] ?? '';
    if (isset($statuts[$statut])) {
        $pdo->prepare('UPDATE commande SET statut = ? WHERE num_commande = ?')->execute([$statut, $num]);
        admin_log($pdo, 'commande_statut', "Commande #{$num} → {$statut}");
        $message = 'Statut mis à jour.';
        if ($statut === 'prete') {
            require_once __DIR__ . '/../includes/notification_service.php';
            notification_commande_prete($pdo, $num);
        }
    }
}

$filter = $_GET['statut'] ?? '';
$q = $_GET['q'] ?? '';
$sql = "
    SELECT c.*, cl.nom_client, cl.prenom_client, cl.email_client
    FROM commande c
    LEFT JOIN client cl ON c.id_client = cl.id_client
    WHERE 1=1
";
$params = [];
if ($filter !== '' && isset($statuts[$filter])) {
    $sql .= ' AND c.statut = ?';
    $params[] = $filter;
}
if ($q !== '') {
    $sql .= ' AND (c.num_commande LIKE ? OR c.num_table LIKE ? OR CONCAT(cl.prenom_client, " ", cl.nom_client) LIKE ?)';
    $qpattern = '%' . $q . '%';
    $params[] = $qpattern;
    $params[] = $qpattern;
    $params[] = $qpattern;
}
$sql .= ' ORDER BY c.date_commande DESC LIMIT 200';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);

admin_shell_start('Admin — Commandes', 'commandes', 'Exploitation', 'Commandes', 'Suivi et modification des statuts.');
?>
<?php if ($message): ?><div class="success-message"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>

<div class="chart-container mb-4">
    <div class="chart-title">Rechercher</div>
    <form method="get" class="row g-3">
        <div class="col-md-6">
            <input type="text" name="q" class="form-control" placeholder="Rechercher par numéro de commande, client, ou table..." value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>">
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

<div class="chart-container mb-4">
    <div class="chart-title">Filtrer</div>
    <form method="get" class="d-flex flex-wrap gap-2 align-items-center">
        <select name="statut" class="form-select" style="max-width:220px; min-width:220px;">
            <option value="">Tous les statuts</option>
            <?php foreach ($statuts as $k => $label): ?>
            <option value="<?php echo $k; ?>"<?php echo $filter === $k ? ' selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn-primary">Appliquer</button>
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
                    <th>Remise</th>
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
                    <td><?php echo format_money((float) $c['montant_total']); ?></td>
                    <td><?php echo format_money((float) ($c['remise_montant'] ?? 0)); ?></td>
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
<?php admin_shell_end(); ?>
