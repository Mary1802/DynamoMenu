<?php

/**
 * Service QR — pont vers App\Service\QrService.
 */

require_once dirname(__DIR__) . '/includes/bootstrap.php';

use App\Service\QrService;

function qr_generate_table_code(int $numTable): string
{
    return QrService::generateTableCode($numTable);
}

function qr_table_entry_url(string $codeTable): string
{
    return app()->qrService()->tableEntryUrl($codeTable);
}

function qr_image_url(string $targetUrl, int $size = 280): string
{
    return QrService::imageUrl($targetUrl, $size);
}

function qr_print_image_url(string $targetUrl, int $size = 480): string
{
    return QrService::printImageUrl($targetUrl, $size);
}
