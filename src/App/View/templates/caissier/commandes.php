<?php

declare(strict_types=1);

use App\Http\Dashboard;
use App\Support\Money;
?>
<!doctype html>
<html lang="fr">
<head>
    <?php Dashboard::assetLinks('Caisse — Commandes'); ?>
</head>
<body class="dashboard-body">
    <div class="sidebar-backdrop" id="sidebarBackdrop" aria-hidden="true"></div>
    <header class="dashboard-topbar">
        <button type="button" class="dashboard-menu-toggle" id="sidebarToggle" aria-label="Menu"><i class="bi bi-list"></i></button>
        <div class="dashboard-topbar-brand">Dynamo<span>Menu</span></div>
        <div style="width:42px;"></div>
    </header>
    <aside class="dashboard-sidebar d-flex flex-column" id="dashboardSidebar">
            <div class="sidebar-brand">
                <div class="brand-logo">DM</div>
                <div class="brand-title">DynamoMenu</div>
                <div class="brand-subtitle">Caisse</div>
            </div>
            <nav class="sidebar-nav">
                <div class="nav-item"><a class="nav-link" href="paiement.php"><span class="nav-icon"><i class="bi bi-credit-card"></i></span><span>Paiements</span></a></div>
                <div class="nav-item"><a class="nav-link active" href="commandes.php"><span class="nav-icon"><i class="bi bi-receipt"></i></span><span>Commandes</span></a></div>
                <div class="nav-item"><a class="nav-link" href="rapports.php"><span class="nav-icon"><i class="bi bi-file-earmark-bar-graph"></i></span><span>Rapports</span></a></div>
                <div class="nav-item"><a class="nav-link" href="parametres.php"><span class="nav-icon"><i class="bi bi-gear"></i></span><span>Paramètres</span></a></div>
            </nav>
            <div class="sidebar-footer"><?php Dashboard::sidebarUserFooter('caissier'); ?></div>
        </aside>
    <div class="dashboard-shell">
        <main class="dashboard-main">
            <header class="dashboard-header dashboard-header--kitchen">
                <div class="header-title">
                    <span class="header-eyebrow">Caisse</span>
                    <h1>Commandes</h1>
                    <p class="mb-0">Tri par statut de paiement</p>
                </div>
                <div class="header-actions">
                    <div class="search-box search-box--mobile-visible">
                        <input type="search" class="search-input" data-dashboard-search placeholder="Nom, tél., table, n° commande…" aria-label="Rechercher">
                        <span class="search-icon"><i class="bi bi-search"></i></span>
                    </div>
                </div>
            </header>

            <?php if (!empty($dashboard_error)): ?>
            <div class="success-message" style="color:var(--danger-color);"><?php echo htmlspecialchars($dashboard_error); ?></div>
            <?php endif; ?>

            <div class="row g-4 commandes-page-layout caissier-commandes-layout">
                <div class="col-lg-6">
                    <div class="dashboard-card commandes-filtrees-card">
                        <div class="card-header">
                            <h3 class="card-title">À encaisser</h3>
                            <span class="section-count">(<?php echo count($commandes_a_encaisser); ?>)</span>
                        </div>
                        <div class="commandes-filtrees-scroll order-scroll-panel kitchen-scroll-panel">
                            <?php if (empty($commandes_a_encaisser)): ?>
                            <div class="empty-state"><p>Aucune commande à encaisser.</p></div>
                            <?php else: ?>
                            <?php foreach ($commandes_a_encaisser as $c): ?>
                            <div class="commande-item mb-3" data-searchable data-search="<?php echo htmlspecialchars(Dashboard::orderSearchBlob($c)); ?>">
                                <div class="commande-header">
                                    <div class="commande-id">#<?php echo str_pad((string) $c['num_commande'], 5, '0', STR_PAD_LEFT); ?></div>
                                    <div class="commande-montant"><?php echo Money::format((float) $c['montant_total']); ?></div>
                                </div>
                                <div class="commande-detail-expanded mt-2">
                                    <?php Dashboard::renderCaissierCommandeDetail($c, $statut_labels); ?>
                                </div>
                                <a href="paiement.php?voir_commande=<?php echo (int) $c['num_commande']; ?>" class="btn-payer btn-sm mt-2 d-inline-block">
                                    <i class="bi bi-cash-coin" aria-hidden="true"></i> Encaisser
                                </a>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="dashboard-card commandes-filtrees-card">
                        <div class="card-header">
                            <h3 class="card-title">Commandes payées</h3>
                            <span class="section-count">(<?php echo count($commandes_payees); ?>)</span>
                        </div>
                        <div class="commandes-filtrees-scroll order-scroll-panel kitchen-scroll-panel">
                            <?php if (empty($commandes_payees)): ?>
                            <div class="empty-state"><p>Aucune commande payée récente.</p></div>
                            <?php else: ?>
                            <?php foreach ($commandes_payees as $c): ?>
                            <div class="commande-item mb-3" data-searchable data-search="<?php echo htmlspecialchars(Dashboard::orderSearchBlob($c) . ' ' . ($c['num_facture'] ?? '')); ?>">
                                <div class="commande-header">
                                    <div class="commande-id">
                                        #<?php echo str_pad((string) $c['num_commande'], 5, '0', STR_PAD_LEFT); ?>
                                        <span class="text-secondary small"> — Facture #<?php echo str_pad((string) $c['num_facture'], 4, '0', STR_PAD_LEFT); ?></span>
                                    </div>
                                    <div class="commande-montant"><?php echo Money::format((float) ($c['total_paye'] ?? $c['montant_total'])); ?></div>
                                </div>
                                <div class="commande-detail-expanded mt-2">
                                    <?php Dashboard::renderCaissierCommandeDetail($c, $statut_labels); ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <?php Dashboard::scripts(); ?>
</body>
</html>
