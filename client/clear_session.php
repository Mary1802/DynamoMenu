<?php
require_once __DIR__ . '/../includes/client_session.php';
client_session_start();
// Effacer uniquement les données de commande, pas toute la session
if (isset($_SESSION['commande_confirmee'])) {
    unset($_SESSION['commande_confirmee']);
}
echo 'OK';