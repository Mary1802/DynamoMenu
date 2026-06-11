<?php

require_once __DIR__ . '/../includes/admin_layout.php';
require_once __DIR__ . '/../includes/report_pdf.php';

admin_require_auth();
$pdo = admin_pdo();

$report = dashboard_report_resolve($pdo, $_GET);
$pdf = dashboard_report_pdf_bytes($report['titre'], $report['totaux'], $report['lignes'], 'Administration');

dashboard_report_send_pdf($pdf, $report['filename'], false);
