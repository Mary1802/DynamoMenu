<?php
require_once __DIR__ . '/../includes/client_session.php';
client_logout();
header('Location: ../client/index.php');
exit;
