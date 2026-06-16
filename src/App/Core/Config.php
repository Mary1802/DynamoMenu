<?php

declare(strict_types=1);

namespace App\Core;

final class Config
{
    /** @var array<string, mixed>|null */
    private static ?array $app = null;

    /** @var array<string, mixed>|null */
    private static ?array $db = null;

    public function __construct(
        private readonly string $configDir
    ) {
    }

    /** @return array<string, mixed> */
    public function app(): array
    {
        if (self::$app === null) {
            $path = $this->configDir . '/app.php';
            self::$app = is_file($path) ? require $path : [];
        }

        return self::$app;
    }

    /** @return array<string, mixed> */
    public function database(): array
    {
        if (self::$db === null) {
            $path = $this->configDir . '/db.php';
            self::$db = is_file($path) ? require $path : [];
        }

        return self::$db;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->app()[$key] ?? $default;
    }

    public function secret(): string
    {
        $secret = (string) $this->get('session_secret', '');
        if ($secret === '' || $secret === 'change-me-in-production') {
            return hash('sha256', dirname($this->configDir, 2) . '|' . php_uname('n'));
        }

        return $secret;
    }

    public function cookiePath(): string
    {
        $basePath = parse_url((string) $this->get('base_url', ''), PHP_URL_PATH);
        if (is_string($basePath) && $basePath !== '' && $basePath !== '/') {
            return rtrim($basePath, '/') . '/';
        }

        return '/';
    }

    public function baseUrl(): string
    {
        $configured = rtrim((string) $this->get('base_url', ''), '/');
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

    public function staffSessionLifetime(): int
    {
        return (int) $this->get('staff_session_lifetime', 28800);
    }

    public function clientSessionLifetime(): int
    {
        return (int) $this->get('client_session_lifetime', 14400);
    }

    public function allowWebSetup(): bool
    {
        return (bool) $this->get('allow_web_setup', false);
    }
}
