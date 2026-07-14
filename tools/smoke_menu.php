<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap/app.php';

use App\Http\Kernel;

$_SERVER['REQUEST_METHOD'] = 'GET';
$_SESSION = [];

ob_start();
try {
    Kernel::dispatch('client/menu.php');
    $html = ob_get_clean();
} catch (Throwable $e) {
    ob_end_clean();
    fwrite(STDERR, 'MENU_FAIL: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

$ok = is_string($html) && (str_contains($html, 'menu') || str_contains($html, 'Menu') || str_contains($html, 'items'));
echo ($ok ? 'MENU_OK' : 'MENU_WEAK') . ' len=' . strlen((string) $html) . PHP_EOL;
exit($ok ? 0 : 1);
