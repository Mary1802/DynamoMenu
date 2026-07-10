<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap/app.php';

use App\Controller\Admin\EmployeController;
use App\Core\Application;

$app = Application::getInstance();
$app->schemaUpgrade()->run();

$pdo = $app->db();
$col = $pdo->query("SHOW COLUMNS FROM employe LIKE 'role'")->fetch(PDO::FETCH_ASSOC);

echo 'ENUM role : ' . ($col['Type'] ?? '?') . PHP_EOL . PHP_EOL;

foreach ($pdo->query('SELECT id_employe, email_employe, role FROM employe ORDER BY id_employe') as $row) {
    $role = (string) $row['role'];
    $valid = isset(EmployeController::ROLES[$role]) ? 'OK' : 'INVALIDE';
    echo "#{$row['id_employe']} {$row['email_employe']} role=[{$role}] {$valid}" . PHP_EOL;
}
