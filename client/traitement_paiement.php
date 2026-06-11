<?php
require_once __DIR__ . '/../includes/client_session.php';
client_session_start();

$db_config = require '../config/db.php';
try {
    $pdo = new PDO(
        "mysql:host=" . $db_config['host'] . ";dbname=" . $db_config['dbname'],
        $db_config['user'],
        $db_config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die('Erreur de connexion: ' . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

client_verify_post_csrf();

$commande_id = $_POST['commande_id'] ?? null;
$mode_paiement = $_POST['mode_paiement'] ?? null;
$montant = $_POST['montant'] ?? null;

if (!$commande_id || !$mode_paiement || !$montant) {
    header('Location: paiement_client.php?commande=' . urlencode((string) $commande_id) . '&error=missing_data');
    exit;
}

$allowedModes = ['carte', 'especes', 'mobile'];
if (!in_array($mode_paiement, $allowedModes, true)) {
    header('Location: paiement_client.php?commande=' . urlencode((string) $commande_id) . '&error=invalid_mode');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM commande WHERE num_commande = ? AND statut IN ('prete', 'livree')");
$stmt->execute([$commande_id]);
$commande = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$commande) {
    header('Location: paiement_client.php?commande=' . urlencode((string) $commande_id) . '&error=commande_not_ready');
    exit;
}

client_require_order_access($commande);

$stmt = $pdo->prepare('SELECT num_facture FROM facture WHERE num_commande = ? LIMIT 1');
$stmt->execute([$commande_id]);
if ($stmt->fetch(PDO::FETCH_ASSOC)) {
    header('Location: paiement_client.php?commande=' . urlencode((string) $commande_id) . '&error=already_paid');
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id_demande FROM demande_paiement WHERE num_commande = ? AND statut = 'en_attente' LIMIT 1");
    $stmt->execute([$commande_id]);
    if ($stmt->fetch(PDO::FETCH_ASSOC)) {
        header('Location: paiement_client.php?commande=' . urlencode((string) $commande_id) . '&error=pending_request');
        exit;
    }

    $stmt = $pdo->prepare('INSERT INTO demande_paiement (num_commande, mode_paiement, montant) VALUES (?, ?, ?)');
    $stmt->execute([$commande_id, $mode_paiement, $montant]);
} catch (PDOException $e) {
    // Table absente sur anciennes installations — on continue avec la session flash
}

$_SESSION['demande_paiement'] = [
    'commande_id' => (int) $commande_id,
    'mode_paiement' => $mode_paiement,
    'montant' => (float) $montant,
];

header('Location: confirmation_paiement.php?commande=' . urlencode((string) $commande_id));
exit;
