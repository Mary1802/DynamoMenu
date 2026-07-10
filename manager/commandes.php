<?php

require_once __DIR__ . '/../bootstrap/app.php';

use App\Http\Dashboard;
use App\Http\Kernel;
use App\Support\Money;

$result = Kernel::forFile(__FILE__);
if ($result !== null) {
    extract($result, EXTR_SKIP);
}

$hasActiveFilters = $q !== '' || $date !== '' || $filtre !== 'toutes';
?>
<!doctype html>
<html lang="fr">
<head>
    <?php Dashboard::assetLinks('Manager — Commandes'); ?>
</head>
<body class="dashboard-body">
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
    <header class="dashboard-topbar">
        <button type="button" class="dashboard-menu-toggle" id="sidebarToggle" aria-label="Menu"><i class="bi bi-list"></i></button>
        <div class="dashboard-topbar-brand">Dynamo<span>Menu</span></div>
        <div style="width:42px;"></div>
    </header>
    <div class="dashboard-shell">
        <aside class="dashboard-sidebar d-flex flex-column" id="dashboardSidebar">
            <div class="sidebar-brand">
                <div class="brand-logo">DM</div>
                <div class="brand-title">DynamoMenu</div>
                <div class="brand-subtitle">Service</div>
            </div>
            <nav class="sidebar-nav">
                <div class="nav-item"><a class="nav-link" href="dashboard.php"><span class="nav-icon"><i class="bi bi-speedometer2"></i></span><span>Dashboard</span></a></div>
                <div class="nav-item"><a class="nav-link active" href="commandes.php"><span class="nav-icon"><i class="bi bi-receipt"></i></span><span>Commandes</span></a></div>
                <div class="nav-item"><a class="nav-link" href="parametres.php"><span class="nav-icon"><i class="bi bi-gear"></i></span><span>Paramètres</span></a></div>
            </nav>
            <div class="sidebar-footer"><?php Dashboard::sidebarUserFooter('manager'); ?></div>
        </aside>
        <main class="dashboard-main manager-commandes-page">
            <header class="dashboard-header dashboard-header--kitchen">
                <div class="header-title">
                    <span class="header-eyebrow">Service</span>
                    <h1>Commandes du service</h1>
                    <p>Prêtes à livrer, déjà livrées, recherche et détails</p>
                </div>
                <div class="header-actions">
                    <div class="search-box search-box--mobile-visible">
                        <input
                            type="search"
                            class="search-input"
                            id="managerInstantSearch"
                            data-dashboard-search
                            value="<?php echo htmlspecialchars($q); ?>"
                            placeholder="Filtrer la liste affichée…"
                            aria-label="Filtrer les résultats affichés"
                            autocomplete="off"
                        >
                        <span class="search-icon"><i class="bi bi-search"></i></span>
                    </div>
                </div>
            </header>

            <div class="dashboard-card manager-commandes-filters mb-3">
                <div class="card-header" style="margin-bottom: 0.85rem;">
                    <h3 class="card-title mb-0">Recherche avancée</h3>
                </div>
                <form method="GET" class="manager-commandes-filters-form">
                    <div class="manager-commandes-filters-grid">
                        <div class="manager-filter-field">
                            <label class="form-label small text-secondary" for="manager-search-q">Recherche</label>
                            <input
                                type="search"
                                id="manager-search-q"
                                name="q"
                                class="form-control"
                                value="<?php echo htmlspecialchars($q); ?>"
                                placeholder="N° commande, nom, téléphone, table…"
                                autocomplete="off"
                            >
                        </div>
                        <div class="manager-filter-field">
                            <label class="form-label small text-secondary" for="manager-search-date">Date</label>
                            <input type="date" id="manager-search-date" name="date" class="form-control" value="<?php echo htmlspecialchars($date); ?>">
                        </div>
                        <div class="manager-filter-field">
                            <label class="form-label small text-secondary" for="manager-search-filtre">Statut</label>
                            <select id="manager-search-filtre" name="filtre" class="form-select">
                                <option value="toutes"<?php echo $filtre === 'toutes' || $filtre === 'service' ? ' selected' : ''; ?>>Toutes (prêtes et livrées)</option>
                                <option value="a_livrer"<?php echo $filtre === 'a_livrer' ? ' selected' : ''; ?>>À livrer uniquement</option>
                                <option value="livrees"<?php echo $filtre === 'livrees' ? ' selected' : ''; ?>>Livrées uniquement</option>
                            </select>
                        </div>
                        <div class="manager-filter-actions">
                            <button type="submit" class="btn-primary">Appliquer</button>
                            <?php if ($hasActiveFilters): ?>
                            <a href="commandes.php" class="btn-details">Réinitialiser</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>

            <div class="mb-3 d-flex flex-wrap gap-2 manager-commandes-quick-filters">
                <a href="commandes.php?filtre=toutes" class="btn-details btn-sm<?php echo ($filtre === 'toutes' || $filtre === 'service') && $q === '' && $date === '' ? ' active' : ''; ?>">Toutes</a>
                <a href="commandes.php?filtre=a_livrer" class="btn-details btn-sm<?php echo $filtre === 'a_livrer' && $q === '' && $date === '' ? ' active' : ''; ?>">À livrer</a>
                <a href="commandes.php?filtre=livrees" class="btn-details btn-sm<?php echo $filtre === 'livrees' && $q === '' && $date === '' ? ' active' : ''; ?>">Livrées</a>
            </div>

            <?php if ($q !== '' || $date !== ''): ?>
            <p class="manager-active-filters text-secondary small mb-3">
                Filtres serveur :
                <?php if ($q !== ''): ?><span class="manager-filter-chip">« <?php echo htmlspecialchars($q); ?> »</span><?php endif; ?>
                <?php if ($date !== ''): ?><span class="manager-filter-chip">Date <?php echo htmlspecialchars($date); ?></span><?php endif; ?>
                — <?php echo (int) $commandes_count; ?> résultat(s)
            </p>
            <?php endif; ?>

            <div class="dashboard-card manager-commandes-results">
                <div class="card-header">
                    <h3 class="card-title">Résultats</h3>
                    <span class="section-count">(<?php echo (int) $commandes_count; ?>)</span>
                </div>

                <div class="manager-commandes-scroll">
                <?php if (empty($commandes)): ?>
                <div class="empty-state"><p>Aucune commande pour ces critères.</p></div>
                <?php else: ?>
                <div class="manager-commandes-list">
                    <?php foreach ($commandes as $c):
                        $statusCss = [
                            'prete' => 'status-prete',
                            'livree' => 'status-livree',
                        ];
                        $sc = $statusCss[$c['statut'] ?? ''] ?? '';
                        ?>
                    <article class="commande-item manager-commande-item" data-searchable data-search="<?php echo htmlspecialchars(Dashboard::orderSearchBlob($c)); ?>">
                        <div class="commande-header">
                            <div class="commande-id">
                                #<?php echo str_pad((string) $c['num_commande'], 5, '0', STR_PAD_LEFT); ?>
                                — Table <?php echo htmlspecialchars((string) ($c['num_table'] ?? '—')); ?>
                            </div>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <span class="order-status <?php echo $sc; ?>">
                                    <?php echo htmlspecialchars($statut_labels[$c['statut']] ?? $c['statut']); ?>
                                </span>
                                <div class="commande-montant mb-0"><?php echo Money::format((float) $c['montant_total']); ?></div>
                            </div>
                        </div>

                        <div class="commande-detail-expanded manager-commande-detail">
                            <?php Dashboard::renderCuisineCommandeFullDetail($c, $statut_labels); ?>
                        </div>

                        <?php if (($c['statut'] ?? '') === 'prete'): ?>
                        <form method="POST" class="manager-commande-action mt-2">
                            <?php Dashboard::csrfField(); ?>
                            <input type="hidden" name="action" value="livree">
                            <input type="hidden" name="commande_id" value="<?php echo (int) $c['num_commande']; ?>">
                            <input type="hidden" name="filtre" value="<?php echo htmlspecialchars($filtre); ?>">
                            <input type="hidden" name="q" value="<?php echo htmlspecialchars($q); ?>">
                            <input type="hidden" name="date" value="<?php echo htmlspecialchars($date); ?>">
                            <button type="submit" class="btn-primary btn-sm">
                                <i class="bi bi-check2-all" aria-hidden="true"></i> Marquer comme livrée
                            </button>
                        </form>
                        <?php endif; ?>
                    </article>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    <?php Dashboard::scripts(); ?>
    <script>
    (function () {
        var serverQ = <?php echo json_encode($q, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        var instant = document.getElementById('managerInstantSearch');
        var serverInput = document.getElementById('manager-search-q');
        if (instant && serverInput && serverQ && !instant.value) {
            instant.value = serverQ;
        }
        if (instant && serverInput) {
            instant.addEventListener('input', function () {
                serverInput.value = instant.value;
            });
        }
    })();
    </script>
</body>
</html>
