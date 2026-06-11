<?php

/**
 * Bloque l'exécution web des scripts d'installation / migration sauf admin connecté.
 */

function setup_is_allowed(): bool
{
    if (PHP_SAPI === 'cli') {
        return true;
    }

    $config = [];
    $file = dirname(__DIR__) . '/config/app.php';
    if (is_file($file)) {
        $config = require $file;
    }

    if (empty($config['allow_web_setup'])) {
        return false;
    }

    require_once __DIR__ . '/staff_auth.php';
    $user = staff_user();

    return $user !== null && ($user['role'] ?? '') === 'admin';
}

function setup_require_access(): void
{
    if (setup_is_allowed()) {
        return;
    }

    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Accès refusé. Connectez-vous en tant qu\'administrateur ou exécutez ce script en CLI.';
    exit;
}
