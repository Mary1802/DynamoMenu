<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap/app.php';

use App\Http\Kernel;

$_SERVER['REQUEST_METHOD'] = 'GET';

ob_start();
Kernel::dispatch('login.php');
$html = ob_get_clean();

if ($html !== false && str_contains($html, 'Connexion')) {
    echo "LOGIN_OK len=" . strlen($html) . PHP_EOL;
    exit(0);
}

fwrite(STDERR, "LOGIN_FAIL\n");
exit(1);
