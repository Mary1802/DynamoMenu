<?php

require_once __DIR__ . '/../includes/admin_layout.php';

use App\Controller\Admin\NotificationController;

admin_init();
$result = (new NotificationController())->handle($_GET, $_POST);
$message = $result['message'];
$notifications = $result['notifications'];
$annee = $result['annee'];
$mois = $result['mois'];
$recherche = $result['recherche'];

admin_shell_start('Admin — Notifications', 'notifications', 'Communication', 'Notifications', 'Historique et envoi de messages promotionnels.');
?>
<?php if ($message): ?><div class="success-message"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>

<div class="chart-container mb-4">
    <div class="chart-title">Diffuser une promo (in-app)</div>
    <form method="post" class="row g-3">
        <div class="col-md-4">
            <label class="form-label text-secondary">Titre</label>
            <input type="text" name="titre" class="form-control" required maxlength="150">
        </div>
        <div class="col-md-8">
            <label class="form-label text-secondary">Message</label>
            <textarea name="message" class="form-control" rows="2" required></textarea>
        </div>
        <div class="col-12">
            <button type="submit" name="send_promo" class="btn-primary" onclick="return confirm('Envoyer à tous les clients ?');">Envoyer à tous</button>
        </div>
    </form>
</div>

<div class="chart-container mb-4">
    <div class="chart-title">Recherche notifications</div>
    <form method="get" class="row g-3">
        <div class="col-md-3">
            <label class="form-label text-secondary">Année</label>
            <input type="text" name="annee" class="form-control" value="<?php echo htmlspecialchars($annee); ?>" placeholder="2025">
        </div>
        <div class="col-md-3">
            <label class="form-label text-secondary">Mois</label>
            <select name="mois" class="form-select">
                <option value="">Tous</option>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?php echo $m; ?>"<?php echo ((string) $m === $mois) ? ' selected' : ''; ?>><?php echo str_pad((string) $m, 2, '0', STR_PAD_LEFT); ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label text-secondary">Nom ou prénom</label>
            <input type="text" name="search" class="form-control" value="<?php echo htmlspecialchars($recherche); ?>" placeholder="Client">
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <button type="submit" class="btn-primary w-100">Rechercher</button>
        </div>
    </form>
</div>

<div class="chart-container">
    <div class="chart-title">Dernières notifications</div>
    <div class="table-responsive-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Client</th>
                    <th>Commande</th>
                    <th>Type</th>
                    <th>Canal</th>
                    <th>Titre</th>
                    <th>Lu</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($notifications as $n): ?>
                <tr>
                    <td><?php echo htmlspecialchars(date('d/m H:i', strtotime($n['date_creation']))); ?></td>
                    <td><?php echo htmlspecialchars(trim(($n['prenom_client'] ?? '') . ' ' . ($n['nom_client'] ?? '—'))); ?></td>
                    <td><?php echo $n['num_commande'] ? '#' . (int) $n['num_commande'] : '—'; ?></td>
                    <td><?php echo htmlspecialchars($n['type_notification']); ?></td>
                    <td><?php echo htmlspecialchars($n['canal']); ?></td>
                    <td><?php echo htmlspecialchars($n['titre']); ?></td>
                    <td><?php echo (int) $n['lu'] ? 'Oui' : 'Non'; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php admin_shell_end(); ?>
