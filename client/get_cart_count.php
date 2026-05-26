<?php
session_start();
header('Content-Type: application/json');

$count = 0;
if (isset($_SESSION['panier'])) {
    foreach ($_SESSION['panier'] as $item) {
        $count += $item['quantite'];
    }
}

echo json_encode(['count' => $count]);