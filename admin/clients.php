<?php

require_once __DIR__ . '/../includes/admin_layout.php';

use App\Controller\Admin\ClientController;

admin_init();
$result = (new ClientController())->handle($_GET, $_POST);
$message = $result['message'];
$clients = $result['clients'];
$q = $result['q'];

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
                    <td><?php echo htmlspecialchars($cl['niveau_label']); ?></td>
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
