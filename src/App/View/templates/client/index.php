<?php

declare(strict_types=1);

use App\Http\ClientPage;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DynamoMenu - Accueil</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/style.css?v=11">
    <link rel="stylesheet" href="../assets/css/client-luxury.css?v=16">
</head>
<body class="client-site client-luxury">
    <?php ClientPage::nav('index'); ?>

    <section class="lux-hero" aria-label="Bienvenue">
        <div class="lux-hero__bg" style="background-image: url('../assets/images/combo/combo burger frites poulet.jpg');"></div>
        <div class="lux-hero__overlay" aria-hidden="true"></div>
        <div class="lux-hero__content">
            <p class="lux-eyebrow mb-3">Commandez. Savourez. Profitez.</p>
            <h1 class="lux-hero__title">Une expérience culinaire digitale, élégante et sans attente</h1>
            <p class="lux-hero__subtitle">Parcourez notre carte et commandez en toute simplicité depuis l'appareil de votre table — préparé avec soin par notre équipe.</p>
            <div class="lux-hero__actions">
                <a class="btn btn-primary btn-lg" href="<?php echo htmlspecialchars($menuUrl); ?>">Commander</a>
                <a class="btn btn-outline-light btn-lg lux-btn-ghost" href="<?php echo htmlspecialchars($menuUrl); ?>">Voir le menu</a>
            </div>
        </div>
    </section>

    <div class="lux-page-intro lux-band lux-band--intro">
        <?php if (($tableError || $tableAccessError) && empty($tableCtx)): ?>
            <?php ClientPage::tableError($tableError ?: 'Table non reconnue. Utilisez l\'appareil configuré pour votre table.'); ?>
        <?php endif; ?>

        <?php if (!empty($recentOrders)): ?>
        <section class="client-recent-orders mb-4" aria-labelledby="recent-orders-title">
            <div class="client-recent-orders__accent" aria-hidden="true"></div>
            <div class="client-recent-orders__inner">
                <header class="client-recent-orders__header">
                    <span class="client-recent-orders__header-icon" aria-hidden="true">
                        <i class="bi bi-receipt"></i>
                    </span>
                    <div>
                        <h2 id="recent-orders-title" class="client-recent-orders__title">Vos commandes récentes</h2>
                        <p class="client-recent-orders__subtitle">Suivez l'état de vos dernières commandes</p>
                    </div>
                </header>

                <div class="client-recent-orders__grid">
                    <?php foreach ($recentOrders as $ro):
                        $countdownActive = !empty($ro['countdown_active']);
                        $prepEndUnix = isset($ro['prep_end_unix']) && $ro['prep_end_unix'] !== null ? (int) $ro['prep_end_unix'] : 0;
                        $remainingSec = (int) ($ro['prep_remaining_seconds'] ?? 0);
                        $mins = (int) floor($remainingSec / 60);
                        $secs = $remainingSec % 60;
                        $initialCountdown = $countdownActive ? sprintf('%02d:%02d', $mins, $secs) : '';
                    ?>
                    <a href="<?php echo htmlspecialchars($ro['detail_url']); ?>"
                       class="client-order-tile<?php echo $countdownActive ? ' has-countdown' : ''; ?>"
                       data-commande="<?php echo (int) $ro['num_commande']; ?>"
                       data-countdown-active="<?php echo $countdownActive ? '1' : '0'; ?>"
                       data-prep-end="<?php echo $prepEndUnix > 0 ? $prepEndUnix : ''; ?>"
                       <?php if (!empty($ro['server_unix'])): ?>data-server-unix="<?php echo (int) $ro['server_unix']; ?>"<?php endif; ?>>
                        <div class="client-order-tile__content">
                            <div class="client-order-tile__head">
                                <span class="client-order-tile__label">Commande</span>
                                <span class="client-order-tile__num">#<?php echo str_pad((string) $ro['num_commande'], 5, '0', STR_PAD_LEFT); ?></span>
                            </div>
                            <span class="client-order-tile__statut <?php echo htmlspecialchars($ro['statut_class'] ?? ''); ?>">
                                <?php echo htmlspecialchars($ro['statut_label']); ?>
                                <?php if (($ro['statut'] ?? '') === 'livree'): ?>
                                <span class="client-order-tile__bon-appetit">Bon appétit !</span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="client-order-tile__timer-pill" data-role="countdown-wrap"<?php echo $countdownActive ? '' : ' hidden'; ?>>
                            <span class="client-order-tile__timer-label">Reste</span>
                            <span class="client-order-tile__timer" data-role="countdown"><?php echo htmlspecialchars($initialCountdown ?: '--:--'); ?></span>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>

                <div class="client-recent-orders__footer">
                    <a href="<?php echo htmlspecialchars($mesCommandesUrl); ?>" class="btn btn-primary btn-sm">Toutes mes commandes</a>
                </div>
            </div>
        </section>
        <?php endif; ?>
    </div>

    <section id="apropos" class="lux-about lux-band lux-band--about" aria-labelledby="lux-about-title">
        <div class="lux-about__inner">
            <p class="lux-eyebrow mb-3">À propos</p>
            <h2 id="lux-about-title" class="lux-about__title">DynamoMenu, votre menu digital de restaurant</h2>
            <p class="lux-about__text">DynamoMenu est une solution de commande digitale pensée pour les restaurants : depuis la tablette ou le smartphone posé sur votre table, consultez la carte en temps réel, composez votre commande et suivez sa préparation jusqu'au service.</p>
            <p class="lux-about__text">Conçu pour simplifier l'expérience client et fluidifier le travail des équipes — cuisine, caisse et administration — DynamoMenu modernise le service en salle sans compromis sur le confort ni la qualité de l'accueil.</p>
            <a class="btn btn-outline-light lux-btn-ghost" href="<?php echo htmlspecialchars($menuUrl); ?>">Découvrir le menu</a>
        </div>
    </section>

    <section class="lux-highlights lux-band lux-band--highlights" aria-labelledby="lux-highlights-title">
        <header class="lux-section-head">
            <p class="lux-eyebrow mb-2">Nos incontournables</p>
            <h2 id="lux-highlights-title" class="lux-section-head__title">Une sélection de nos meilleures créations</h2>
            <p class="lux-section-head__text">Combos généreux, plats signatures et accompagnements — tout est disponible à la commande depuis votre table.</p>
        </header>
        <div class="lux-highlights__grid">
            <a href="<?php echo htmlspecialchars($menuUrl); ?>" class="lux-highlight-card">
                <div class="lux-highlight-card__img">
                    <img src="../assets/images/combo/combo burger frites poulet.jpg" alt="Combo burger frites poulet" loading="lazy" decoding="async">
                </div>
                <div class="lux-highlight-card__body">
                    <h3 class="lux-highlight-card__name">Combo Burger Frites</h3>
                    <p class="lux-highlight-card__desc">Formule complète pour un repas généreux et savoureux.</p>
                </div>
            </a>
            <a href="<?php echo htmlspecialchars($menuUrl); ?>" class="lux-highlight-card">
                <div class="lux-highlight-card__img">
                    <img src="../assets/images/combo/combo sandwich frites.png" alt="Combo sandwich frites" loading="lazy" decoding="async">
                </div>
                <div class="lux-highlight-card__body">
                    <h3 class="lux-highlight-card__name">Combo Sandwich</h3>
                    <p class="lux-highlight-card__desc">Léger, croquant et parfait pour une pause déjeuner.</p>
                </div>
            </a>
            <a href="<?php echo htmlspecialchars($menuUrl); ?>" class="lux-highlight-card">
                <div class="lux-highlight-card__img">
                    <img src="../assets/images/plats_principaux/makoso.jpg" alt="Plat principal" loading="lazy" decoding="async">
                </div>
                <div class="lux-highlight-card__body">
                    <h3 class="lux-highlight-card__name">Plats signatures</h3>
                    <p class="lux-highlight-card__desc">Recettes maison préparées à la commande par notre cuisine.</p>
                </div>
            </a>
            <a href="<?php echo htmlspecialchars($menuUrl); ?>" class="lux-highlight-card">
                <div class="lux-highlight-card__img">
                    <img src="../assets/images/desserts/tarte aux pommes.jpg" alt="Dessert" loading="lazy" decoding="async">
                </div>
                <div class="lux-highlight-card__body">
                    <h3 class="lux-highlight-card__name">Desserts &amp; douceurs</h3>
                    <p class="lux-highlight-card__desc">Terminez votre repas sur une note sucrée et gourmande.</p>
                </div>
            </a>
        </div>
    </section>

    <?php ClientPage::footer(); ?>

    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script>
        (function () {
            const nav = document.querySelector('.client-navbar');
            if (!nav) return;
            const onScroll = () => nav.classList.toggle('is-scrolled', window.scrollY > 24);
            window.addEventListener('scroll', onScroll, { passive: true });
            onScroll();
        })();
    </script>
    <script>
        function updateCartBadge() {
            fetch('get_cart_count.php')
                .then(response => response.json())
                .then(data => {
                    document.querySelectorAll('[data-cart-count]').forEach(function (cartBadge) {
                        cartBadge.textContent = data.count;
                        cartBadge.style.display = data.count === 0 ? 'none' : 'inline-block';
                    });
                });
        }
        document.addEventListener('DOMContentLoaded', updateCartBadge);
        setInterval(updateCartBadge, 5000);

        <?php if (!empty($tableCtx) && !empty($indexUrl)): ?>
        if (window.history.replaceState && /[?&]err=/.test(window.location.search)) {
            window.history.replaceState(null, '', <?php echo json_encode($indexUrl, JSON_UNESCAPED_SLASHES); ?>);
        }
        window.addEventListener('pageshow', function (event) {
            if (event.persisted && /[?&]err=/.test(window.location.search)) {
                window.location.replace(<?php echo json_encode($indexUrl, JSON_UNESCAPED_SLASHES); ?>);
            }
        });
        <?php endif; ?>
    </script>
    <?php if (!empty($recentOrders)): ?>
    <script>
        (function () {
            const tiles = document.querySelectorAll('.client-order-tile[data-commande]');
            if (tiles.length === 0) return;

            let serverClockOffset = 0;
            const firstWithServer = document.querySelector('.client-order-tile[data-server-unix]');
            if (firstWithServer) {
                serverClockOffset = parseInt(firstWithServer.dataset.serverUnix, 10) - Math.floor(Date.now() / 1000);
            }

            function serverNowUnix() {
                return Math.floor(Date.now() / 1000) + serverClockOffset;
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

            function applyStatutClass(el, statut) {
                el.classList.remove('is-ready', 'is-done');
                if (statut === 'prete') {
                    el.classList.add('is-ready');
                } else if (statut === 'livree' || statut === 'annulee') {
                    el.classList.add('is-done');
                }
            }

            function updateTileCountdown(tile) {
                const active = tile.dataset.countdownActive === '1';
                const prepEnd = parseInt(tile.dataset.prepEnd || '0', 10);
                const wrap = tile.querySelector('[data-role="countdown-wrap"]');
                const display = tile.querySelector('[data-role="countdown"]');

                if (!active || !prepEnd || !wrap || !display) {
                    wrap?.setAttribute('hidden', '');
                    tile.classList.remove('has-countdown');
                    return;
                }

                wrap.removeAttribute('hidden');
                tile.classList.add('has-countdown');
                display.textContent = formatCountdown(prepEnd - serverNowUnix());
            }

            function refreshOrderFromApi(tile) {
                const id = tile.dataset.commande;
                if (!id) return;

                fetch('../api/client/commande_statut.php?commande=' + encodeURIComponent(id))
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (data.error) return;

                        if (data.payee) {
                            tile.remove();
                            const grid = document.querySelector('.client-recent-orders__grid');
                            if (grid && !grid.querySelector('.client-order-tile')) {
                                const section = document.querySelector('.client-recent-orders');
                                section?.remove();
                            }
                            return;
                        }

                        tile.dataset.countdownActive = data.countdown_active ? '1' : '0';
                        tile.dataset.prepEnd = data.prep_end_unix ? String(data.prep_end_unix) : '';

                        if (data.server_unix) {
                            serverClockOffset = parseInt(data.server_unix, 10) - Math.floor(Date.now() / 1000);
                        }

                        const statutEl = tile.querySelector('.client-order-tile__statut');
                        if (statutEl && data.statut_label) {
                            let html = data.statut_label;
                            if (data.statut === 'livree' || data.livree) {
                                html += ' <span class="client-order-tile__bon-appetit">Bon appétit !</span>';
                            }
                            statutEl.innerHTML = html;
                            applyStatutClass(statutEl, data.statut);
                        }

                        updateTileCountdown(tile);
                    })
                    .catch(function () {});
            }

            tiles.forEach(updateTileCountdown);
            setInterval(function () { tiles.forEach(updateTileCountdown); }, 1000);
            tiles.forEach(refreshOrderFromApi);
            setInterval(function () { tiles.forEach(refreshOrderFromApi); }, 8000);
        })();
    </script>
    <?php endif; ?>
</body>
</html>
