<?php

require __DIR__ . '/../bootstrap/app.php';

use App\Core\Application;

$app = Application::getInstance();
$pdo = $app->db();

$sql = <<<'SQL'
SELECT c.num_commande, c.statut, c.montant_total
FROM commande c
WHERE c.statut = 'livree'
  AND NOT EXISTS (SELECT 1 FROM facture f WHERE f.num_commande = c.num_commande)
LIMIT 1
SQL;

$row = $pdo->query($sql)->fetch(PDO::FETCH_ASSOC);
echo 'Before: ';
var_export($row);
echo PHP_EOL;

if ($row === false) {
    echo "No awaiting order.\n";
    exit(0);
}

$id = (int) $row['num_commande'];
$montant = (float) $row['montant_total'];
$result = $app->paiementService()->processPayment($id, 'especes', $montant);
echo 'Payment: ';
var_export($result);
echo PHP_EOL;

$still = (int) $pdo->query(
    "SELECT COUNT(*) FROM commande c WHERE c.num_commande = {$id} AND c.statut = 'livree'
     AND NOT EXISTS (SELECT 1 FROM facture f WHERE f.num_commande = c.num_commande)"
)->fetchColumn();
echo "Still in awaiting list: {$still}\n";
