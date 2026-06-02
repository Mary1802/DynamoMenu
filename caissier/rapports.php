<?php

require_once __DIR__ . '/../includes/staff_auth.php';
staff_require(['caissier']);

$db_config = require '../config/db.php';
require_once '../includes/dashboard_helpers.php';
require_once __DIR__ . '/../includes/money.php';

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

$jour = $_GET['date'] ?? date('Y-m-d');
$mois = $_GET['mois'] ?? date('Y-m');

$rapport_jour = dashboard_sales_totals($pdo, 'day', $jour);
$rapport_mois = dashboard_sales_totals($pdo, 'month', $mois);

$stmt = $pdo->prepare("
    SELECT f.*, c.num_table, cl.nom_client, cl.prenom_client
    FROM facture f
    JOIN commande c ON f.num_commande = c.num_commande
    LEFT JOIN client cl ON c.id_client = cl.id_client
    WHERE DATE(date_facture) = ?
    ORDER BY f.date_facture DESC
");
$stmt->execute([$jour]);
$lignes_jour = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="fr">
<head>
    <?php dashboard_asset_links('Caissier — Rapports'); ?>
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
                <div class="brand-subtitle">Caisse</div>
            </div>
            <nav class="sidebar-nav">
                <div class="nav-item"><a class="nav-link" href="paiement.php"><span class="nav-icon"><i class="bi bi-credit-card"></i></span><span>Paiements</span></a></div>
                <div class="nav-item"><a class="nav-link" href="commandes.php"><span class="nav-icon"><i class="bi bi-receipt"></i></span><span>Commandes</span></a></div>
                <div class="nav-item"><a class="nav-link active" href="rapports.php"><span class="nav-icon"><i class="bi bi-file-earmark-bar-graph"></i></span><span>Rapports</span></a></div>
                <div class="nav-item"><a class="nav-link" href="parametres.php"><span class="nav-icon"><i class="bi bi-gear"></i></span><span>Paramètres</span></a></div>
            </nav>
            <div class="sidebar-footer">
                <?php dashboard_sidebar_user_footer('caissier'); ?>
            </div>
        </aside>
        <main class="dashboard-main">
            <header class="dashboard-header">
                <div class="header-title">
                    <span class="header-eyebrow">Caisse</span>
                    <h1>Rapports journalier & mensuel</h1>
                </div>
            </header>

            <div class="stats-row mb-4">
                <div class="stat-box">
                    <div class="stat-value"><?php echo format_money((float) $rapport_jour['ca']); ?></div>
                    <div class="stat-label">CA jour <?php echo htmlspecialchars($jour); ?></div>
                    <div class="small text-secondary mt-1"><?php echo (int) $rapport_jour['nb']; ?> paiements</div>
                    <div class="payment-split-row">
                        <div class="payment-split-box">
                            <div class="label">Cash</div>
                            <div class="value"><?php echo format_money((float) $rapport_jour['ca_especes']); ?></div>
                        </div>
                        <div class="payment-split-box">
                            <div class="label">Mobile money</div>
                            <div class="value"><?php echo format_money((float) $rapport_jour['ca_mobile']); ?></div>
                        </div>
                    </div>
                </div>
                <div class="stat-box">
                    <div class="stat-value"><?php echo format_money((float) $rapport_mois['ca']); ?></div>
                    <div class="stat-label">CA mois <?php echo htmlspecialchars($mois); ?></div>
                    <div class="small text-secondary mt-1"><?php echo (int) $rapport_mois['nb']; ?> paiements</div>
                    <div class="payment-split-row">
                        <div class="payment-split-box">
                            <div class="label">Cash</div>
                            <div class="value"><?php echo format_money((float) $rapport_mois['ca_especes']); ?></div>
                        </div>
                        <div class="payment-split-box">
                            <div class="label">Mobile money</div>
                            <div class="value"><?php echo format_money((float) $rapport_mois['ca_mobile']); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <form class="chart-container mb-4 row g-3" method="get">
                <div class="col-md-4">
                    <label class="form-label text-secondary">Jour</label>
                    <input type="date" name="date" class="form-control" value="<?php echo htmlspecialchars($jour); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label text-secondary">Mois</label>
                    <input type="month" name="mois" class="form-control" value="<?php echo htmlspecialchars($mois); ?>">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn-primary w-100">Actualiser</button>
                </div>
            </form>

            <div class="chart-container">
                <div class="chart-title">Détail du jour</div>
                <div class="table-responsive-wrap">
                    <table class="data-table">
                        <thead>
                            <tr><th>Facture</th><th>Commande</th><th>Client</th><th>Table</th><th>Mode</th><th>Montant</th><th>Heure</th></tr>
                        </thead>
                        <tbody>
                        <?php if (empty($lignes_jour)): ?>
                            <tr><td colspan="7">Aucun paiement ce jour</td></tr>
                        <?php else: foreach ($lignes_jour as $l): ?>
                            <tr>
                                <td>#<?php echo (int) $l['num_facture']; ?></td>
                                <td>#<?php echo str_pad($l['num_commande'], 5, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo htmlspecialchars(trim(($l['prenom_client'] ?? '') . ' ' . ($l['nom_client'] ?? ''))); ?></td>
                                <td><?php echo htmlspecialchars((string) $l['num_table']); ?></td>
                                <td><?php echo htmlspecialchars(dashboard_mode_paiement_label((string) $l['mode_paiement'])); ?></td>
                                <td><?php echo format_money((float) $l['total_paye']); ?></td>
                                <td><?php echo date('H:i', strtotime($l['date_facture'])); ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    <?php dashboard_scripts(); ?>
</body>
</html>
