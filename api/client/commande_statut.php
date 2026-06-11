<?php
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 2) . '/includes/client_session.php';
client_session_start();

$num = isset($_GET['commande']) ? (int) $_GET['commande'] : 0;
$token = trim((string) ($_GET['token'] ?? ''));
if ($num <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Commande invalide']);
    exit;
}

$db_config = require dirname(__DIR__, 2) . '/config/db.php';

try {
    $pdo = new PDO(
        'mysql:host=' . $db_config['host'] . ';dbname=' . $db_config['dbname'],
        $db_config['user'],
        $db_config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $stmt = $pdo->prepare('
        SELECT c.num_commande, c.statut, c.montant_total, c.date_commande, c.mode_paiement_souhaite,
               c.num_table, cl.nom_client, cl.prenom_client
        FROM commande c
        LEFT JOIN table_restaurant t ON c.num_table = t.num_table
        LEFT JOIN client cl ON c.id_client = cl.id_client
        WHERE c.num_commande = ?
    ');
    $stmt->execute([$num]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        http_response_code(404);
        echo json_encode(['error' => 'Commande introuvable']);
        exit;
    }

    if (!client_can_access_order($row, $token !== '' ? $token : null)) {
        http_response_code(403);
        echo json_encode(['error' => 'Accès refusé']);
        exit;
    }

    $labels = [
        'en_attente' => 'En attente en cuisine',
        'en_preparation' => 'En préparation',
        'prete' => 'Prête — en cours de service',
        'livree' => 'Livrée à votre table',
        'annulee' => 'Annulée',
    ];

    echo json_encode([
        'num_commande' => (int) $row['num_commande'],
        'statut' => $row['statut'],
        'statut_label' => $labels[$row['statut']] ?? $row['statut'],
        'montant_total' => (float) $row['montant_total'],
        'num_table' => $row['num_table'],
        'pret' => $row['statut'] === 'prete',
        'livree' => $row['statut'] === 'livree',
    ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur']);
}
