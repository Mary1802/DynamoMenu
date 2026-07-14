<?php

declare(strict_types=1);

use App\Http\Dashboard;
use App\Service\ReportService;
use App\Support\Money;

$nomsMois = ReportService::MONTH_NAMES;
?>
<!doctype html>
<html lang="fr">
<head>
    <?php Dashboard::assetLinks('Caissier — Rapports'); ?>
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
                <div class="nav-item"><a class="nav-link" href="commandes.php"><span class="nav-icon"><i class="bi bi-receipt"></i></span><span>Commandes</span></a></div>
                <div class="nav-item"><a class="nav-link active" href="rapports.php"><span class="nav-icon"><i class="bi bi-file-earmark-bar-graph"></i></span><span>Rapports</span></a></div>
                <div class="nav-item"><a class="nav-link" href="parametres.php"><span class="nav-icon"><i class="bi bi-gear"></i></span><span>Paramètres</span></a></div>
            </nav>
            <div class="sidebar-footer">
                <?php Dashboard::sidebarUserFooter('caissier'); ?>
            </div>
        </aside>
    <div class="dashboard-shell">
        <main class="dashboard-main">
            <header class="dashboard-header">
                <div class="header-title">
                    <span class="header-eyebrow">Caisse</span>
                    <h1>Rapports de ventes</h1>
                    <p class="mb-0">Filtre par mois et année — export PDF en fin de mois</p>
                </div>
            </header>

            <form class="chart-container mb-4 row g-3 align-items-end" method="get" id="rapportFilterForm">
                <div class="col-md-4">
                    <label class="form-label text-secondary" for="filtreAnnee">Année</label>
                    <select name="annee" id="filtreAnnee" class="form-select">
                        <?php foreach ($annees as $a): ?>
                        <option value="<?php echo $a; ?>"<?php echo $a === $annee ? ' selected' : ''; ?>><?php echo $a; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label text-secondary" for="filtreMois">Mois</label>
                    <select name="mois" id="filtreMois" class="form-select">
                        <?php foreach ($nomsMois as $num => $nom): ?>
                        <option value="<?php echo $num; ?>"<?php echo $num === $moisNum ? ' selected' : ''; ?>><?php echo $nom; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn-primary w-100">Afficher le rapport</button>
                </div>
            </form>

            <div class="stats-row stats-row--3 mb-4">
                <div class="stat-box">
                    <div class="stat-value"><?php echo Money::format((float) $rapport_mois['ca']); ?></div>
                    <div class="stat-label">CA — <?php echo htmlspecialchars($moisLabel); ?></div>
                    <div class="small text-secondary mt-1"><?php echo (int) $rapport_mois['nb']; ?> paiements</div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Cash</div>
                    <div class="stat-value"><?php echo Money::format((float) $rapport_mois['ca_especes']); ?></div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Mobile money</div>
                    <div class="stat-value"><?php echo Money::format((float) $rapport_mois['ca_mobile']); ?></div>
                </div>
            </div>

            <div class="chart-container mb-4 rapport-export-actions">
                <div class="chart-title mb-3">Imprimer ou télécharger</div>
                <div class="rapport-export-grid">
                    <section class="rapport-export-card" aria-labelledby="rapportMensuelTitle">
                        <h3 class="rapport-export-card-title" id="rapportMensuelTitle">Rapport mensuel</h3>
                        <p class="rapport-export-card-desc">Tout le mois affiché (<?php echo htmlspecialchars($moisLabel); ?>)</p>
                        <div class="rapport-export-buttons">
                            <a href="rapport_imprimer.php?type=mensuel&amp;<?php echo $exportBase; ?>" class="rapport-export-btn rapport-export-btn--print" target="_blank" rel="noopener">
                                <i class="bi bi-printer" aria-hidden="true"></i>
                                <span>Imprimer</span>
                            </a>
                            <a href="rapport_export.php?type=mensuel&amp;<?php echo $exportBase; ?>" class="rapport-export-btn rapport-export-btn--download">
                                <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i>
                                <span>Télécharger</span>
                            </a>
                        </div>
                    </section>
                    <section class="rapport-export-card" aria-labelledby="rapportJournalierTitle">
                        <h3 class="rapport-export-card-title" id="rapportJournalierTitle">Rapport journalier</h3>
                        <p class="rapport-export-card-desc">Un jour précis du mois sélectionné</p>
                        <div class="rapport-export-day">
                            <label class="rapport-export-day-label" for="jourExport">Jour</label>
                            <select id="jourExport" class="form-select rapport-export-day-select" aria-label="Jour du rapport journalier">
                                <?php for ($d = 1; $d <= $daysInMonth; $d++): ?>
                                <option value="<?php echo $d; ?>"<?php echo $d === $jourExport ? ' selected' : ''; ?>><?php echo $d; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="rapport-export-buttons">
                            <a href="rapport_imprimer.php?type=journalier&amp;<?php echo $exportBase; ?>&amp;jour=<?php echo $jourExport; ?>" class="rapport-export-btn rapport-export-btn--print rapport-jour-link" target="_blank" rel="noopener" data-base="rapport_imprimer.php?type=journalier&amp;<?php echo $exportBase; ?>">
                                <i class="bi bi-printer" aria-hidden="true"></i>
                                <span>Imprimer</span>
                            </a>
                            <a href="rapport_export.php?type=journalier&amp;<?php echo $exportBase; ?>&amp;jour=<?php echo $jourExport; ?>" class="rapport-export-btn rapport-export-btn--download rapport-jour-link" data-base="rapport_export.php?type=journalier&amp;<?php echo $exportBase; ?>">
                                <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i>
                                <span>Télécharger</span>
                            </a>
                        </div>
                    </section>
                </div>
            </div>

            <div class="chart-container" id="rapport-print-area">
                <div class="chart-title">Détail du mois — <?php echo htmlspecialchars($moisLabel); ?></div>
                <div class="table-responsive-wrap caisse-section-scroll order-scroll-panel">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date et heure</th>
                                <th>Facture</th>
                                <th>Commande</th>
                                <th>Client</th>
                                <th>Table</th>
                                <th>Mode</th>
                                <th>Montant</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($lignes_mois)): ?>
                            <tr><td colspan="7">Aucun paiement pour cette période</td></tr>
                        <?php else: foreach ($lignes_mois as $l): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i', strtotime($l['date_facture'])); ?></td>
                                <td>#<?php echo (int) $l['num_facture']; ?></td>
                                <td>#<?php echo str_pad((string) $l['num_commande'], 5, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo htmlspecialchars(trim(($l['prenom_client'] ?? '') . ' ' . ($l['nom_client'] ?? ''))); ?></td>
                                <td><?php echo htmlspecialchars((string) ($l['num_table'] ?? '')); ?></td>
                                <td><?php echo htmlspecialchars(Dashboard::modePaiementLabel((string) $l['mode_paiement'])); ?></td>
                                <td><?php echo Money::format((float) $l['total_paye']); ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    <?php Dashboard::scripts(); ?>
    <script>
    (function () {
        var anneeSelect = document.getElementById('filtreAnnee');
        var moisSelect = document.getElementById('filtreMois');
        var jourSelect = document.getElementById('jourExport');
        if (!jourSelect) return;

        function daysInMonth(year, month) {
            return new Date(year, month, 0).getDate();
        }

        function periodQuery() {
            var annee = anneeSelect ? anneeSelect.value : '<?php echo (int) $annee; ?>';
            var mois = moisSelect ? moisSelect.value : '<?php echo (int) $moisNum; ?>';
            return 'annee=' + encodeURIComponent(annee) + '&mois=' + encodeURIComponent(mois);
        }

        function rebuildDayOptions() {
            var annee = parseInt(anneeSelect ? anneeSelect.value : '<?php echo (int) $annee; ?>', 10);
            var mois = parseInt(moisSelect ? moisSelect.value : '<?php echo (int) $moisNum; ?>', 10);
            var total = daysInMonth(annee, mois);
            var previous = parseInt(jourSelect.value || '1', 10);
            var today = new Date();
            var defaultDay = (annee === today.getFullYear() && mois === (today.getMonth() + 1))
                ? today.getDate()
                : total;
            var selected = Math.min(Math.max(1, previous || defaultDay), total);

            jourSelect.innerHTML = '';
            for (var d = 1; d <= total; d++) {
                var option = document.createElement('option');
                option.value = String(d);
                option.textContent = String(d);
                if (d === selected) {
                    option.selected = true;
                }
                jourSelect.appendChild(option);
            }
        }

        function updateExportLinks() {
            var query = periodQuery();
            document.querySelectorAll('.rapport-jour-link').forEach(function (link) {
                var type = link.classList.contains('rapport-export-btn--print') ? 'rapport_imprimer.php' : 'rapport_export.php';
                link.setAttribute('data-base', type + '?type=journalier&' + query);
                link.href = type + '?type=journalier&' + query + '&jour=' + encodeURIComponent(jourSelect.value);
            });
        }

        function syncPeriod() {
            rebuildDayOptions();
            updateExportLinks();
        }

        if (anneeSelect) {
            anneeSelect.addEventListener('change', syncPeriod);
        }
        if (moisSelect) {
            moisSelect.addEventListener('change', syncPeriod);
        }

        jourSelect.addEventListener('change', updateExportLinks);
        syncPeriod();
    })();
    </script>
</body>
</html>
