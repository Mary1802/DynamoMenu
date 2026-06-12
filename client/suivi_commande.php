<?php

require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/../includes/client_session.php';
require_once __DIR__ . '/../includes/money.php';

use App\Controller\Client\OrderTrackingController;

$data = (new OrderTrackingController())->show($_GET);
if ($data === null) {
    header('Location: index.php');
    exit;
}

$num_commande = $data['num_commande'];
$commande = $data['commande'];
$lignes = $data['lignes'];
$facture = $data['facture'];
$statutInitial = $data['statutInitial'];
$tableLabel = $data['tableLabel'];
$clientNom = $data['clientNom'];
$modePaiement = $data['modePaiement'];
$remise = $data['remise'];
$sousTotalLignes = $data['sousTotalLignes'];
$indexUrl = $data['indexUrl'];

require_once __DIR__ . '/../includes/table_context.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suivi commande — DynamoMenu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background: radial-gradient(circle at top left, rgba(255,111,31,0.16), transparent 28%),
                        linear-gradient(180deg, #071119 0%, #0b1521 40%, #0f172a 100%);
            color: #f8fafc;
            min-height: 100vh;
        }
        body::before { opacity: 0.35; }
        .suivi-wrap { max-width: 720px; margin: 0 auto; padding: 1.5rem 1rem 3rem; }
        .suivi-card {
            background: rgba(7, 7, 12, 0.88);
            border: 1px solid rgba(255,111,31,0.18);
            border-radius: 24px;
            padding: 1.75rem;
            box-shadow: 0 20px 70px rgba(0,0,0,0.35);
        }
        .suivi-meta {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem 1rem;
            margin: 1.25rem 0;
        }
        @media (max-width: 575.98px) { .suivi-meta { grid-template-columns: 1fr; } }
        .meta-box {
            background: rgba(255,255,255,0.04);
            border-radius: 12px;
            padding: 0.85rem 1rem;
            border: 1px solid rgba(255,255,255,0.06);
        }
        .meta-label { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.06em; color: rgba(255,255,255,0.5); }
        .meta-value { font-weight: 600; color: #fff; margin-top: 0.2rem; }
        .status-pill {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 999px;
            background: rgba(255,111,31,0.15);
            color: #f4c95a;
            font-weight: 600;
            margin: 0.5rem 0 1rem;
        }
        .status-pill.is-ready {
            background: rgba(40,167,69,0.2);
            color: #7dcea0;
            animation: pulse 1.5s ease infinite;
        }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.75; } }
        .line-item {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.65rem 0;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            font-size: 0.95rem;
        }
        .line-item small { color: rgba(255,255,255,0.55); display: block; }
        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 1.25rem;
            font-weight: 700;
            color: #ff6f1f;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(255,111,31,0.25);
        }
        #notifyBanner { display: none; }
        #notifyBanner.show { display: block; }
    </style>
</head>
<body>
    <header class="navbar navbar-dark px-3 py-3">
        <a class="navbar-brand fw-bold text-white" href="<?php echo htmlspecialchars($indexUrl); ?>">DynamoMenu</a>
    </header>

    <main class="suivi-wrap">
        <div class="suivi-card">
            <h1 class="h3 mb-1 text-white">Récapitulatif de commande</h1>
            <p class="text-secondary mb-0">Merci pour votre commande</p>

            <div id="notifyBanner" class="alert alert-success mt-3 mb-0 py-2" role="alert">
                <strong>Votre commande est prête.</strong> Elle va vous être apportée à table.
            </div>
            <div id="notifList" class="mt-3" style="display:none;"></div>

            <p class="status-pill" id="statusPill"><?php echo htmlspecialchars($statutInitial); ?></p>

            <div class="suivi-meta">
                <div class="meta-box">
                    <div class="meta-label">N° commande</div>
                    <div class="meta-value">#<?php echo str_pad((string) $num_commande, 5, '0', STR_PAD_LEFT); ?></div>
                </div>
                <div class="meta-box">
                    <div class="meta-label">Table</div>
                    <div class="meta-value"><?php echo htmlspecialchars($tableLabel); ?></div>
                </div>
                <div class="meta-box">
                    <div class="meta-label">Client</div>
                    <div class="meta-value"><?php echo htmlspecialchars($clientNom ?: '—'); ?></div>
                </div>
                <div class="meta-box">
                    <div class="meta-label">Moyen de paiement</div>
                    <div class="meta-value"><?php echo htmlspecialchars($modePaiement ?: 'À régler en caisse'); ?></div>
                </div>
                <?php if ($facture): ?>
                <div class="meta-box">
                    <div class="meta-label">N° facture</div>
                    <div class="meta-value">F-<?php echo str_pad((string) $facture['num_facture'], 4, '0', STR_PAD_LEFT); ?></div>
                </div>
                <div class="meta-box">
                    <div class="meta-label">Montant payé</div>
                    <div class="meta-value"><?php echo format_money((float) $facture['total_paye']); ?></div>
                </div>
                <?php endif; ?>
            </div>

            <h2 class="h5 text-white mt-2 mb-3">Addition</h2>
            <div class="mb-2">
                <?php foreach ($lignes as $l): ?>
                <div class="line-item">
                    <span>
                        <?php echo htmlspecialchars($l['nom']); ?>
                        <small>× <?php echo (int) $l['quantite']; ?></small>
                    </span>
                    <span><?php echo format_money((float) $l['sous_total']); ?></span>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if ($remise > 0): ?>
            <div class="d-flex justify-content-between text-secondary small mb-1">
                <span>Sous-total articles</span>
                <span><?php echo format_money($sousTotalLignes); ?></span>
            </div>
            <div class="d-flex justify-content-between text-success small mb-2">
                <span>Réduction fidélité</span>
                <span>− <?php echo format_money($remise); ?></span>
            </div>
            <?php endif; ?>

            <div class="total-row">
                <span>Total TTC</span>
                <span><?php echo format_money((float) $commande['montant_total']); ?></span>
            </div>

            <div class="d-flex flex-column flex-sm-row gap-2 mt-4">
                <a href="<?php echo htmlspecialchars($indexUrl); ?>" class="btn btn-outline-light flex-fill">
                    <i class="bi bi-house-door"></i> Retour à l'accueil
                </a>
                <a href="<?php echo htmlspecialchars(table_link('nouvelle_commande.php')); ?>" class="btn btn-primary flex-fill" style="background:#ff6f1f;border-color:#ff6f1f;">
                    <i class="bi bi-plus-circle"></i> Commander à nouveau
                </a>
            </div>
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