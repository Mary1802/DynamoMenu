<?php

/** Pont procédural → App\Service\ReportService / ReportPdfGenerator */
require_once __DIR__ . '/bootstrap.php';

function dashboard_report_resolve(PDO $pdo, array $query): array
{
    return app()->reportService()->resolveReport($query);
}

function dashboard_report_pdf_bytes(string $titre, array $totaux, array $lignes, string $contextLabel = 'Caisse'): string
{
    return \App\Service\ReportPdfGenerator::generate($titre, $totaux, $lignes, $contextLabel);
}

function dashboard_report_send_pdf(string $pdf, string $filename, bool $inline): void
{
    header('Content-Type: application/pdf');
    header('Content-Length: ' . strlen($pdf));
    $disp = $inline ? 'inline' : 'attachment';
    header('Content-Disposition: ' . $disp . '; filename="' . $filename . '"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    echo $pdf;
    exit;
}
