<?php

function app_config(): array
{
    static $config = null;
    if ($config === null) {
        $path = dirname(__DIR__) . '/config/app.php';
        $config = file_exists($path) ? require $path : ['base_url' => ''];
    }

    return $config;
}

function app_base_url(): string
{
    $configured = rtrim((string) (app_config()['base_url'] ?? ''), '/');
    if ($configured !== '') {
        return $configured;
    }

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $markers = ['/client/', '/admin/', '/caissier/', '/cuisine/', '/api/'];
    foreach ($markers as $marker) {
        $pos = strpos($script, $marker);
        if ($pos !== false) {
            return $scheme . '://' . $host . substr($script, 0, $pos);
        }
    }

    return $scheme . '://' . $host;
}
