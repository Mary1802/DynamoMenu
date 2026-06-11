<?php
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 2) . '/includes/client_session.php';
require_once dirname(__DIR__, 2) . '/includes/table_context.php';
client_session_start();

if (!table_session()) {
    http_response_code(403);
    echo json_encode(['error' => 'Session table requise'], JSON_UNESCAPED_UNICODE);
    exit;
}

$db_config = require dirname(__DIR__, 2) . '/config/db.php';
require_once dirname(__DIR__, 2) . '/includes/fidelity_service.php';

$email = trim($_GET['email'] ?? $_POST['email'] ?? '');

try {
    $pdo = new PDO(
        'mysql:host=' . $db_config['host'] . ';dbname=' . $db_config['dbname'],
        $db_config['user'],
        $db_config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    fidelity_ensure($pdo);

    if ($email === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Email requis'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt = $pdo->prepare('SELECT id_client, points, niveau_fidelite, nom_client, prenom_client FROM client WHERE email_client = ?');
    $stmt->execute([$email]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$client) {
        echo json_encode(['exists' => false, 'points' => 0, 'rewards' => fidelity_list_rewards($pdo)], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $client = fidelity_get_client($pdo, (int) $client['id_client']);
    $rewards = fidelity_list_rewards($pdo);
    $available = array_values(array_filter($rewards, static fn($r) => (int) $client['points'] >= (int) $r['points_requis']));

    echo json_encode([
        'exists' => true,
        'id_client' => (int) $client['id_client'],
        'points' => (int) $client['points'],
        'niveau' => $client['niveau_fidelite'],
        'niveau_label' => fidelity_niveau_label($client['niveau_fidelite']),
        'rewards' => $rewards,
        'rewards_available' => $available,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur'], JSON_UNESCAPED_UNICODE);
}
