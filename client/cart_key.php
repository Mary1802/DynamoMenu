<?php

require_once __DIR__ . '/../includes/client_session.php';
client_session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/cart_helpers.php';

$name = trim($_GET['name'] ?? $_POST['name'] ?? '');
$category = trim($_GET['category'] ?? $_POST['category'] ?? '');
$type = trim($_GET['type'] ?? $_POST['type'] ?? 'menu_item');
$personnalisation = trim($_GET['personnalisation'] ?? $_POST['personnalisation'] ?? '');

echo json_encode([
    'key' => cart_make_key($type, $name, $category, $personnalisation),
], JSON_UNESCAPED_UNICODE);
