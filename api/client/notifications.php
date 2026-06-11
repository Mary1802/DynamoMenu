<?php
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 2) . '/includes/client_session.php';
client_session_start();

$db_config = require dirname(__DIR__, 2) . '/config/db.php';
require_once dirname(__DIR__, 2) . '/includes/notification_service.php';

$commande = isset($_GET['commande']) ? (int) $_GET['commande'] : 0;
$token = trim((string) ($_GET['token'] ?? ''));
$markRead = isset($_GET['mark_read']) && $_GET['mark_read'] === '1';

try {
    $pdo = new PDO(
        'mysql:host=' . $db_config['host'] . ';dbname=' . $db_config['dbname'],
        $db_config['user'],
        $db_config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    if ($commande <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Commande invalide']);
        exit;
    }

    $stmt = $pdo->prepare('SELECT num_commande, num_table, id_client FROM commande WHERE num_commande = ?');
    $stmt->execute([$commande]);
    $orderRow = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$orderRow) {
        http_response_code(404);
        echo json_encode(['error' => 'Commande introuvable']);
        exit;
    }

    if (!client_can_access_order($orderRow, $token !== '' ? $token : null)) {
        http_response_code(403);
        echo json_encode(['error' => 'Accès refusé']);
        exit;
    }

    $idClient = (int) ($orderRow['id_client'] ?? 0);
    if ($idClient <= 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Client introuvable']);
        exit;
    }

    if ($markRead) {
        notification_mark_read_for_commande($pdo, $idClient, $commande);
    }

    $items = notification_list_for_client($pdo, $idClient, $commande);
    $unread = count(array_filter($items, static fn($n) => !(int) $n['lu']));

    echo json_encode([
        'notifications' => $items,
        'unread_count' => $unread,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur']);
}
