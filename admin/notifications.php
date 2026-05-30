<?php

require_once __DIR__ . '/../includes/admin_layout.php';
require_once __DIR__ . '/../includes/notification_service.php';

$pdo = admin_pdo();
notification_ensure($pdo);

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_promo'])) {
    $titre = trim($_POST['titre'] ?? '');
    $msg = trim($_POST['message'] ?? '');
    if ($titre !== '' && $msg !== '') {
        $clients = $pdo->query('SELECT id_client FROM client WHERE id_client IS NOT NULL')->fetchAll(PDO::FETCH_COLUMN);
        $count = 0;
        foreach ($clients as $idClient) {
            notification_create($pdo, (int) $idClient, null, 'promo', $titre, $msg, 'in_app');
            $count++;
        }
        admin_log($pdo, 'promo_broadcast', "Promo envoyée à {$count} clients : {$titre}", 'notifications');
        $message = "Notification promo envoyée à {$count} client(s).";
    }
}

$notifications = notification_admin_list($pdo, 150);

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
