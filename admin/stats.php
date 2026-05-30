<?php

require_once __DIR__ . '/../includes/admin_layout.php';
require_once __DIR__ . '/../includes/schema_upgrade.php';

$pdo = admin_pdo();
schema_upgrade($pdo);

$caJour = (float) $pdo->query("SELECT COALESCE(SUM(total_paye), 0) FROM facture WHERE DATE(date_facture) = CURDATE()")->fetchColumn();
$caMois = (float) $pdo->query("SELECT COALESCE(SUM(total_paye), 0) FROM facture WHERE YEAR(date_facture) = YEAR(CURDATE()) AND MONTH(date_facture) = MONTH(CURDATE())")->fetchColumn();
$commandesJour = (int) $pdo->query("SELECT COUNT(*) FROM commande WHERE DATE(date_commande) = CURDATE()")->fetchColumn();
$clientsTotal = (int) $pdo->query('SELECT COUNT(*) FROM client')->fetchColumn();
$pointsTotal = (int) $pdo->query('SELECT COALESCE(SUM(points), 0) FROM client')->fetchColumn();

$parStatut = $pdo->query("
    SELECT statut, COUNT(*) AS nb
    FROM commande
    GROUP BY statut
")->fetchAll(PDO::FETCH_KEY_PAIR);

$topPlats = $pdo->query("
    SELECT p.nom_plat, SUM(d.quantite) AS qte, SUM(d.sous_total) AS ca
    FROM contient d
    JOIN plat p ON d.id_plat = p.id_plat
    GROUP BY p.id_plat, p.nom_plat
    ORDER BY qte DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

admin_shell_start('Admin — Statistiques', 'stats', 'Analyse', 'Statistiques', 'Chiffre d\'affaires et activité.');
?>

<div class="stats-grid mb-4">
    <div class="stat-card">
        <div class="stat-label">CA aujourd'hui</div>
        <div class="stat-value"><?php echo number_format($caJour, 2); ?> €</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">CA ce mois</div>
        <div class="stat-value"><?php echo number_format($caMois, 2); ?> €</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Commandes du jour</div>
        <div class="stat-value"><?php echo $commandesJour; ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Clients / points</div>
        <div class="stat-value"><?php echo $clientsTotal; ?> <span class="stat-sub">/ <?php echo $pointsTotal; ?> pts</span></div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="chart-container">
            <div class="chart-title">Commandes par statut</div>
            <ul class="list-unstyled mb-0">
            <?php foreach ($parStatut as $statut => $nb): ?>
                <li class="d-flex justify-content-between py-2 border-bottom border-secondary">
                    <span><?php echo htmlspecialchars($statut); ?></span>
                    <strong><?php echo (int) $nb; ?></strong>
                </li>
            <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="chart-container">
            <div class="chart-title">Top plats</div>
            <div class="table-responsive-wrap">
                <table class="data-table">
                    <thead><tr><th>Plat</th><th>Qté</th><th>CA</th></tr></thead>
                    <tbody>
                    <?php foreach ($topPlats as $p): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($p['nom_plat']); ?></td>
                            <td><?php echo (int) $p['qte']; ?></td>
                            <td><?php echo number_format((float) $p['ca'], 2); ?> €</td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php admin_shell_end(); ?>
