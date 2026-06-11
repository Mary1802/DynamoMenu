<?php



require_once __DIR__ . '/../includes/staff_auth.php';

staff_require(['caissier']);



require_once __DIR__ . '/../includes/report_pdf.php';



$db_config = require __DIR__ . '/../config/db.php';

$pdo = new PDO(

    'mysql:host=' . $db_config['host'] . ';dbname=' . $db_config['dbname'],

    $db_config['user'],

    $db_config['password'],

    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]

);



$report = dashboard_report_resolve($pdo, $_GET);

$pdf = dashboard_report_pdf_bytes($report['titre'], $report['totaux'], $report['lignes'], 'Caisse');

dashboard_report_send_pdf($pdf, $report['filename'], true);

