<?php

require_once __DIR__ . '/../bootstrap/app.php';

use App\Core\Application;
use App\Http\ClientPage;
use App\Http\Kernel;
use App\Support\Money;

$result = Kernel::forFile(__FILE__);
if ($result !== null) {
    extract($result, EXTR_SKIP);
}
if ($result === null || empty($result)) {
    Application::getInstance()->tableContextService()->redirectToIndex();
}
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
            padding: 0.3rem 0.65rem;
            border-radius: 999px;
            background: rgba(255,111,31,0.15);
            color: #f4c95a;
            font-size: 0.78rem;
            font-weight: 600;
            line-height: 1.3;
            margin: 0.5rem 0 1rem;
            max-width: 100%;
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
        #countdownBlock { display: none; }
        #countdownBlock.is-active { display: block; }
        .countdown-ring {
            text-align: center;
            padding: 1.25rem;
            margin: 1rem 0;
            border-radius: 16px;
            background: rgba(255,111,31,0.08);
            border: 1px solid rgba(255,111,31,0.22);
        }
        .countdown-time {
            font-size: 2.5rem;
            font-weight: 800;
            font-variant-numeric: tabular-nums;
            color: #ff6f1f;
            letter-spacing: 0.04em;
        }
        .countdown-label { color: rgba(255,255,255,0.65); font-size: 0.9rem; margin-top: 0.35rem; }
        .prep-badge { color: rgba(255,255,255,0.5); font-size: 0.8rem; }
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
                <strong><i class="bi bi-check-circle"></i> Votre commande est prête !</strong> Elle va vous être apportée à table.
            </div>
            <div id="notifList" class="mt-3" style="display:none;"></div>

            <div id="countdownBlock" class="countdown-ring<?php echo !empty($countdown['countdown_active']) ? ' is-active' : ''; ?>">
                <div class="countdown-label">Temps estimé restant</div>
                <div class="countdown-time" id="countdownDisplay">--:--</div>
                <div class="countdown-label">La cuisine prépare votre commande</div>
            </div>

            <p class="status-pill<?php echo ($statutCode ?? '') === 'prete' ? ' is-ready' : ''; ?>" id="statusPill"><?php echo htmlspecialchars($statutInitial); ?></p>

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
                    <div class="meta-label">Temps estimé</div>
                    <div class="meta-value">~<?php echo (int) ($prepEstimeMinutes ?? 15); ?> min</div>
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
                    <div class="meta-value"><?php echo Money::format((float) $facture['total_paye']); ?></div>
                </div>
                <?php endif; ?>
            </div>

            <h2 class="h5 text-white mt-2 mb-3">Addition</h2>
            <div class="mb-2">
                <?php foreach ($lignes as $l): ?>
                <div class="line-item">
                    <span>
                        <?php echo htmlspecialchars($l['nom']); ?>
                        <small>× <?php echo (int) $l['quantite']; ?>
                        <?php if (!empty($l['temps_preparation_min'])): ?>
                            · <?php echo (int) $l['temps_preparation_min']; ?> min / unité
                        <?php endif; ?>
                        </small>
                    </span>
                    <span><?php echo Money::format((float) $l['sous_total']); ?></span>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="total-row">
                <span>Total TTC</span>
                <span><?php echo Money::format((float) $commande['montant_total']); ?></span>
            </div>

            <div class="d-flex flex-column flex-sm-row gap-2 mt-4">
                <a href="<?php echo htmlspecialchars($mesCommandesUrl ?? 'mes_commandes.php'); ?>" class="btn btn-outline-light flex-fill">
                    <i class="bi bi-list-ul"></i> Mes commandes
                </a>
                <a href="<?php echo htmlspecialchars($indexUrl); ?>" class="btn btn-outline-light flex-fill">
                    <i class="bi bi-house-door"></i> Retour à l'accueil
                </a>
                <a href="<?php echo htmlspecialchars(ClientPage::tableLink('nouvelle_commande.php')); ?>" class="btn btn-primary flex-fill" style="background:#ff6f1f;border-color:#ff6f1f;">
                    <i class="bi bi-plus-circle"></i> Commander à nouveau
                </a>
            </div>
        </div>
    </main>

    <script>
        const commandeId = <?php echo (int) $num_commande; ?>;
        let notifiedReady = <?php echo ($statutCode ?? '') === 'prete' ? 'true' : 'false'; ?>;
        let countdownActive = <?php echo !empty($countdown['countdown_active']) ? 'true' : 'false'; ?>;
        let prepEndUnix = <?php echo isset($countdown['prep_end_unix']) && $countdown['prep_end_unix'] !== null ? (int) $countdown['prep_end_unix'] : 'null'; ?>;
        let serverClockOffset = 0;
        <?php if (!empty($countdown['server_unix'])): ?>
        serverClockOffset = <?php echo (int) $countdown['server_unix']; ?> - Math.floor(Date.now() / 1000);
        <?php endif; ?>
        let countdownTimer = null;

        function serverNowUnix() {
            return Math.floor(Date.now() / 1000) + serverClockOffset;
        }

        function getRemainingSeconds() {
            if (!prepEndUnix) return 0;
            return Math.max(0, prepEndUnix - serverNowUnix());
        }

        function formatCountdown(sec) {
            sec = Math.max(0, Math.floor(sec));
            const h = Math.floor(sec / 3600);
            const m = Math.floor((sec % 3600) / 60);
            const s = sec % 60;
            if (h > 0) {
                return h + ':' + String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
            }
            return String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
        }

        function updateCountdownDisplay() {
            const block = document.getElementById('countdownBlock');
            const display = document.getElementById('countdownDisplay');
            if (!countdownActive || !prepEndUnix) {
                block.classList.remove('is-active');
                return;
            }
            block.classList.add('is-active');
            display.textContent = formatCountdown(getRemainingSeconds());
        }

        function ensureCountdownTimer() {
            if (countdownTimer || !countdownActive) return;
            updateCountdownDisplay();
            countdownTimer = setInterval(updateCountdownDisplay, 1000);
        }

        function stopCountdown() {
            countdownActive = false;
            prepEndUnix = null;
            if (countdownTimer) {
                clearInterval(countdownTimer);
                countdownTimer = null;
            }
            document.getElementById('countdownBlock').classList.remove('is-active');
        }

        function applyCountdownFromServer(data) {
            if (!data.countdown_active || !data.prep_end_unix) {
                stopCountdown();
                return;
            }
            countdownActive = true;
            prepEndUnix = parseInt(data.prep_end_unix, 10);
            if (data.server_unix) {
                serverClockOffset = parseInt(data.server_unix, 10) - Math.floor(Date.now() / 1000);
            }
            ensureCountdownTimer();
            updateCountdownDisplay();
        }

        function showReadyState() {
            if (notifiedReady) return;
            notifiedReady = true;
            stopCountdown();
            document.getElementById('notifyBanner').classList.add('show');
            showBrowserNotification('DynamoMenu', 'Votre commande est prête !');
        }

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

                    if (data.statut === 'prete' || data.pret) {
                        showReadyState();
                        return;
                    }

                    applyCountdownFromServer(data);
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
                            showReadyState();
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
        if (notifiedReady) {
            document.getElementById('notifyBanner').classList.add('show');
        }
        ensureCountdownTimer();
        refreshStatus();
        refreshNotifications();
        setInterval(refreshStatus, 8000);
        setInterval(refreshNotifications, 10000);
    </script>
</body>
</html>