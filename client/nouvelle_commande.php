<?php

session_start();

require_once __DIR__ . '/../includes/table_context.php';

$db_config = require __DIR__ . '/../config/db.php';
try {
    $pdo = new PDO(
        'mysql:host=' . $db_config['host'] . ';dbname=' . $db_config['dbname'],
        $db_config['user'],
        $db_config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    bootstrap_table_context($pdo);
} catch (PDOException $e) {
    // Table optionnelle : on vide quand même le panier
}

unset($_SESSION['panier'], $_SESSION['commande_confirmee'], $_SESSION['suivi_commande_id']);

header('Location: ' . table_link('menu.php'));
exit;
