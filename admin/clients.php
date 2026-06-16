<?php

require_once __DIR__ . '/../bootstrap/app.php';

use App\Http\AdminPage;
use App\Http\Kernel;

$result = Kernel::forFile(__FILE__);
if ($result !== null) {
    extract($result, EXTR_SKIP);
}

AdminPage::shellStart('Admin — Clients', 'clients', 'CRM', 'Clients', 'Consultez les profils clients enregistrés lors des commandes.');
?>
<?php if ($message): ?><div class="success-message"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>

<div class="chart-container mb-4">
    <div class="chart-title">Rechercher</div>
    <form method="get" class="row g-3">
        <div class="col-md-8">
            <input type="text" name="q" class="form-control" placeholder="Rechercher par nom, email ou téléphone..." value="<?php echo htmlspecialchars($q); ?>">
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
                    <th>Téléphone</th>
                    <th>Inscription</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($clients as $cl): ?>
                <tr>
                    <td><?php echo htmlspecialchars(trim(($cl['prenom_client'] ?? '') . ' ' . $cl['nom_client'])); ?></td>
                    <td><?php echo htmlspecialchars($cl['email_client'] ?? '—'); ?></td>
                    <td><?php echo htmlspecialchars($cl['telephone_client'] ?? '—'); ?></td>
                    <td><?php echo !empty($cl['date_inscription']) ? htmlspecialchars(date('d/m/Y', strtotime($cl['date_inscription']))) : '—'; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php AdminPage::shellEnd(); ?>
