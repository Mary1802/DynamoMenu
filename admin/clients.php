<?php

require_once __DIR__ . '/../includes/admin_layout.php';
require_once __DIR__ . '/../includes/fidelity_service.php';

$pdo = admin_init();
fidelity_ensure($pdo);

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adjust_points'])) {
    $id = (int) $_POST['id_client'];
    $delta = (int) $_POST['delta'];
    $note = trim($_POST['note'] ?? 'Ajustement admin');
    if ($id > 0 && $delta !== 0) {
        fidelity_add_points($pdo, $id, $delta, 'ajustement', $note);
        admin_log($pdo, 'fidelite_ajust', "Client #{$id} : {$delta} pts — {$note}", 'fidelite');
        $message = 'Points mis à jour.';
    }
}

$q = $_GET['q'] ?? '';
$sql = '
    SELECT id_client, nom_client, prenom_client, email_client, telephone_client, points, niveau_fidelite, date_inscription
    FROM client
    WHERE 1=1
';
$params = [];
if ($q !== '') {
    $sql .= ' AND (nom_client LIKE ? OR prenom_client LIKE ? OR email_client LIKE ?)';
    $qpattern = '%' . $q . '%';
    $params[] = $qpattern;
    $params[] = $qpattern;
    $params[] = $qpattern;
}
$sql .= ' ORDER BY points DESC, nom_client LIMIT 300';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

admin_shell_start('Admin — Clients', 'clients', 'CRM', 'Clients & points', 'Consultez les profils et ajustez les points fidélité.');
?>
<?php if ($message): ?><div class="success-message"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>

<div class="chart-container mb-4">
    <div class="chart-title">Rechercher</div>
    <form method="get" class="row g-3">
        <div class="col-md-8">
            <input type="text" name="q" class="form-control" placeholder="Rechercher par nom ou email..." value="<?php echo htmlspecialchars($q); ?>">
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn-primary w-100">Rechercher</button>
        </div>
        <?php if ($q !== ''): ?>
        <div class="col-md-2">
            <a href="clients.php" class="btn btn-outline-secondary w-100">Réinitialiser</a>
        </div>
        <?php endif; ?>
    </form>
</div>

<div class="chart-container">
    <div class="chart-title">Clients (<?php echo count($clients); ?>)</div>
    <div class="table-responsive-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Tél.</th>
                    <th>Points</th>
                    <th>Niveau</th>
                    <th>Ajustement</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($clients as $cl): ?>
                <tr>
                    <td><?php echo htmlspecialchars(trim(($cl['prenom_client'] ?? '') . ' ' . $cl['nom_client'])); ?></td>
                    <td><?php echo htmlspecialchars($cl['email_client'] ?? '—'); ?></td>
                    <td><?php echo htmlspecialchars($cl['telephone_client'] ?? '—'); ?></td>
                    <td><strong><?php echo (int) $cl['points']; ?></strong></td>
                    <td><?php echo htmlspecialchars(fidelity_niveau_label($cl['niveau_fidelite'] ?? fidelity_niveau((int) $cl['points']))); ?></td>
                    <td>
                        <form method="post" class="d-flex gap-1 flex-wrap align-items-center">
                            <input type="hidden" name="id_client" value="<?php echo (int) $cl['id_client']; ?>">
                            <input type="number" name="delta" class="form-control form-control-sm" style="width:80px;" placeholder="+/-" required>
                            <input type="text" name="note" class="form-control form-control-sm" style="min-width:120px;" placeholder="Motif">
                            <button type="submit" name="adjust_points" class="btn-details btn-sm">Appliquer</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php admin_shell_end(); ?>
