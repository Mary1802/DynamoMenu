<?php

require_once dirname(__DIR__) . '/includes/app_url.php';

/**
 * Génère un identifiant unique pour une table.
 */
function qr_generate_table_code(int $numTable): string
{
    return 'TBL-' . str_pad((string) $numTable, 3, '0', STR_PAD_LEFT) . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
}

/**
 * URL scannée par le client (accueil avec table en session).
 */
function qr_table_entry_url(string $codeTable): string
{
    return app_base_url() . '/client/index.php?t=' . rawurlencode($codeTable);
}

/**
 * URL d'image QR (service externe, sans dépendance Composer).
 */
function qr_image_url(string $targetUrl, int $size = 280): string
{
    return 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size
        . '&data=' . rawurlencode($targetUrl);
}
