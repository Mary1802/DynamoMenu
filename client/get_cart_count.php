<?php

require_once __DIR__ . '/../includes/client_session.php';
client_session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/cart_helpers.php';

$count = 0;
$keys = [];
if (isset($_SESSION['panier']) && is_array($_SESSION['panier'])) {
    foreach ($_SESSION['panier'] as $item) {
        $count += (int) ($item['quantite'] ?? 1);
        if (!empty($item['cart_key'])) {
            $keys[] = $item['cart_key'];
        }
    }
}

echo json_encode([
    'count' => $count,
    'keys' => array_values(array_unique($keys)),
], JSON_UNESCAPED_UNICODE);
