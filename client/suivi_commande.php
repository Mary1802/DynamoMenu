<?php
session_start();

$num_commande = (int) ($_GET['commande'] ?? $_SESSION['suivi_commande_id'] ?? 0);
if ($num_commande <= 0) {
    header('Location: index.php');
    exit;
}

$db_config = require __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/table_context.php';

try {
    $pdo = new PDO(
        'mysql:host=' . $db_config['host'] . ';dbname=' . $db_config['dbname'],
        $db_config['user'],
        $db_config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die('Erreur de connexion');
}

$stmt = $pdo->prepare("
    SELECT c.*, cl.nom_client, cl.prenom_client, cl.email_client, cl.telephone_client, t.num_table
    FROM commande c
    LEFT JOIN client cl ON c.id_client = cl.id_client
    LEFT JOIN table_restaurant t ON c.num_table = t.num_table
    WHERE c.num_commande = ?
");
$stmt->execute([$num_commande]);
$commande = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$commande) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT COALESCE(p.nom_plat, b.nom_boisson) AS nom, d.quantite, d.sous_total
    FROM contient d
    LEFT JOIN plat p ON d.id_plat = p.id_plat
    LEFT JOIN boisson b ON d.id_boisson = b.id_boisson
    WHERE d.num_commande = ?
");
$stmt->execute([$num_commande]);
$lignes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$confirm = $_SESSION['commande_confirmee'] ?? null;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suivi commande — DynamoMenu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .suivi-card {
            max-width: 640px;
            margin: 2rem auto;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 1rem;
            padding: 1.5rem;
        }
        .status-pill {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 999px;
            background: rgba(255,111,31,0.15);
            color: #ffb47f;
            font-weight: 600;
            margin: 1rem 0;
        }
        .status-pill.is-ready {
            background: rgba(40,167,69,0.2);
            color: #7dcea0;
            animation: pulse 1.5s ease infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.75; }
        }
        #notifyBanner { display: none; }
        #notifyBanner.show { display: block; }
    </style>
</head>
<body>
    <header class="navbar navbar-dark px-4 py-3">
        <a class="navbar-brand fw-bold text-white" href="index.php">DynamoMenu</a>
    </header>

    <main class="container px-3 pb-5">
        <div class="suivi-card text-white">
            <h1 class="h3 mb-2">Votre commande</h1>
            <p class="text-secondary mb-0">N° <?php echo str_pad($num_commande, 5, '0', STR_PAD_LEFT); ?> — Table <?php echo htmlspecialchars((string) $commande['num_table']); ?></p>

            <div id="notifyBanner" class="alert alert-success mt-3" role="alert">
                <strong>Votre commande est prête.</strong> Elle va vous être apportée à table.
            </div>
            <div id="notifList" class="mt-3" style="display:none;"></div>

            <p class="status-pill" id="statusPill">Chargement du statut…</p>

            <h2 class="h5 mt-4">Addition</h2>
            <ul class="list-unstyled mb-3">
                <?php foreach ($lignes as $l): ?>
                <li class="d-flex justify-content-between border-bottom border-secondary py-2">
                    <span><?php echo htmlspecialchars($l['nom']); ?> × <?php echo (int) $l['quantite']; ?></span>
                    <span><?php echo number_format($l['sous_total'], 2, ',', ' '); ?> €</span>
                </li>
                <?php endforeach; ?>
            </ul>
            <div class="d-flex justify-content-between fw-bold fs-5 text-warning">
                <span>Total TTC</span>
                <span><?php echo number_format($commande['montant_total'], 2, ',', ' '); ?> €</span>
            </div>

            <?php if (!empty($commande['mode_paiement_souhaite'])): ?>
            <p class="mt-3 mb-0 small text-secondary">
                Paiement prévu :
                <strong><?php echo $commande['mode_paiement_souhaite'] === 'mobile_money' ? 'Mobile money' : 'Cash'; ?></strong>
            </p>
            <?php endif; ?>

            <p class="mt-4 small text-secondary">
                Client : <?php echo htmlspecialchars(trim($commande['prenom_client'] . ' ' . $commande['nom_client'])); ?>
            </p>

            <a href="menu.php" class="btn btn-outline-light mt-3">Commander à nouveau</a>
        </div>
    </main>

    <script>
        const commandeId = <?php echo (int) $num_commande; ?>;
        let notifiedReady = false;

        function showBrowserNotification(title, body) {
            if ('Notification' in window && Notification.permission === 'granted') {
                new Notification(title, { body: body });
            }
        }

        function refreshStatus() {
            fetch('../api/client/commande_statut.php?commande=' + commandeId)
                .then(r => r.json())
                .then(data => {
                    if (data.error) return;
                    const pill = document.getElementById('statusPill');
                    pill.textContent = data.statut_label;
                    pill.classList.toggle('is-ready', data.statut === 'prete');
                    if (data.statut === 'prete' && !notifiedReady) {
                        notifiedReady = true;
                        document.getElementById('notifyBanner').classList.add('show');
                        showBrowserNotification('DynamoMenu', 'Votre commande est prête !');
                    }
                })
                .catch(() => {});
        }

        function refreshNotifications() {
            fetch('../api/client/notifications.php?commande=' + commandeId)
                .then(r => r.json())
                .then(data => {
                    if (data.error || !data.notifications) return;
                    const unread = data.notifications.filter(n => !parseInt(n.lu, 10));
                    const list = document.getElementById('notifList');
                    if (unread.length === 0) {
                        list.style.display = 'none';
                        return;
                    }
                    list.style.display = 'block';
                    list.innerHTML = unread.map(n => {
                        const ready = (n.titre || '').toLowerCase().includes('prête');
                        if (ready && !notifiedReady) {
                            notifiedReady = true;
                            document.getElementById('notifyBanner').classList.add('show');
                            showBrowserNotification(n.titre, n.message);
                        }
                        return '<div class="alert alert-info py-2 mb-2 small"><strong>' +
                            escapeHtml(n.titre) + '</strong><br>' + escapeHtml(n.message) + '</div>';
                    }).join('');
                    if (unread.length > 0) {
                        fetch('../api/client/notifications.php?commande=' + commandeId + '&mark_read=1');
                    }
                })
                .catch(() => {});
        }

        function escapeHtml(s) {
            const d = document.createElement('div');
            d.textContent = s || '';
            return d.innerHTML;
        }

        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission();
        }
        refreshStatus();
        refreshNotifications();
        setInterval(refreshStatus, 8000);
        setInterval(refreshNotifications, 10000);
    </script>
</body>
</html>
