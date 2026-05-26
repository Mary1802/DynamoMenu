<?php
session_start();

// Configuration de la base de données
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

// Vérifier les données POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$commande_id = $_POST['commande_id'] ?? null;
$mode_paiement = $_POST['mode_paiement'] ?? null;
$montant = $_POST['montant'] ?? null;

if (!$commande_id || !$mode_paiement || !$montant) {
    header('Location: paiement_client.php?commande=' . $commande_id . '&error=missing_data');
    exit;
}

// Vérifier que la commande existe et est prête
$stmt = $pdo->prepare("SELECT * FROM commande WHERE num_commande = ? AND statut IN ('prete', 'livree')");
$stmt->execute([$commande_id]);
$commande = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$commande) {
    header('Location: paiement_client.php?commande=' . $commande_id . '&error=commande_not_ready');
    exit;
}

// Vérifier que la commande n'est pas déjà payée
$stmt = $pdo->prepare("SELECT * FROM facture WHERE num_commande = ?");
$stmt->execute([$commande_id]);
$facture_existante = $stmt->fetch(PDO::FETCH_ASSOC);

if ($facture_existante) {
    header('Location: paiement_client.php?commande=' . $commande_id . '&error=already_paid');
    exit;
}

// Enregistrer la demande de paiement dans la base de données
$stmt = $pdo->prepare("
    INSERT INTO demande_paiement (num_commande, mode_paiement, montant, statut)
    VALUES (?, ?, ?, 'en_attente')
");
$stmt->execute([$commande_id, $mode_paiement, $montant]);

// Stocker aussi en session pour la confirmation
$_SESSION['demande_paiement'] = [
    'commande_id' => $commande_id,
    'mode_paiement' => $mode_paiement,
    'montant' => $montant,
    'timestamp' => time()
];

// Rediriger vers la page de confirmation
header('Location: confirmation_paiement.php?commande=' . $commande_id);
exit;