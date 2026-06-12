<?php

require_once __DIR__ . '/../includes/admin_layout.php';
require_once __DIR__ . '/../includes/money.php';

use App\Controller\Admin\ReportController;
use App\Service\ReportService;

admin_init();
$data = (new ReportController())->index($_GET);
$rapport_jour = $data['rapport_jour'];
$rapport_mois = $data['rapport_mois'];
$lignes_mois = $data['lignes_mois'];
$annee = $data['annee'];
$moisNum = $data['moisNum'];
$moisLabel = $data['moisLabel'];
$daysInMonth = $data['daysInMonth'];
$jourExport = $data['jourExport'];
$exportBase = $data['exportBase'];
$annees = $data['annees'];
$nomsMois = ReportService::MONTH_NAMES;

admin_shell_start(
    'Admin — Rapports ventes',
    'rapports',
    'Administration',
    'Rapports journalier & mensuel',
    'Ventilation cash / mobile money — export PDF net pour impression et téléchargement.'
);
?>
<div class="stats-row mb-4">
    <div class="stat-box dashboard-card">
        <div class="stat-value"><?php echo format_money((float) $rapport_jour['ca']); ?></div>
        <div class="stat-label">CA aujourd'hui</div>
        <div class="payment-split-row">
            <div class="payment-split-box"><div class="label">Cash</div><div class="value"><?php echo format_money((float) $rapport_jour['ca_especes']); ?></div></div>
            <div class="payment-split-box"><div class="label">Mobile money</div><div class="value"><?php echo format_money((float) $rapport_jour['ca_mobile']); ?></div></div>
        </div>
    </div>
    <div class="stat-box dashboard-card">
        <div class="stat-value"><?php echo format_money((float) $rapport_mois['ca']); ?></div>
        <div class="stat-label">CA — <?php echo htmlspecialchars($moisLabel); ?></div>
        <div class="payment-split-row">
            <div class="payment-split-box"><div class="label">Cash</div><div class="value"><?php echo format_money((float) $rapport_mois['ca_especes']); ?></div></div>
            <div class="payment-split-box"><div class="label">Mobile money</div><div class="value"><?php echo format_money((float) $rapport_mois['ca_mobile']); ?></div></div>
        </div>
    </div>
</div>

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

<div class="chart-container mb-4 rapport-export-actions">
    <div class="chart-title mb-3">Imprimer ou télécharger (PDF)</div>
    <div class="rapport-export-grid">
        <section class="rapport-export-card" aria-labelledby="rapportMensuelTitle">
            <h3 class="rapport-export-card-title" id="rapportMensuelTitle">Rapport mensuel</h3>
            <p class="rapport-export-card-desc">Tableau complet — <?php echo htmlspecialchars($moisLabel); ?></p>
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

<div class="chart-container mb-4">
    <div class="chart-title">Détail du mois — <?php echo htmlspecialchars($moisLabel); ?></div>
    <div class="table-responsive-wrap">
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
                    <td><?php echo htmlspecialchars(dashboard_mode_paiement_label((string) $l['mode_paiement'])); ?></td>
                    <td><?php echo format_money((float) $l['total_paye']); ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
(function () {
    var jourSelect = document.getElementById('jourExport');
    if (!jourSelect) return;
    document.querySelectorAll('.rapport-jour-link').forEach(function (link) {
        function updateHref() {
            var base = link.getAttribute('data-base');
            if (base) {
                link.href = base + '&jour=' + encodeURIComponent(jourSelect.value);
            }
        }
        updateHref();
        jourSelect.addEventListener('change', updateHref);
    });
})();
</script>

<?php admin_shell_end(); ?>
