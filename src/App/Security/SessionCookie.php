<?php

declare(strict_types=1);

namespace App\Security;

use App\Core\Config;

final class SessionCookie
{
    public function __construct(
        private readonly Config $config
    ) {
    }

    public function isHttps(): bool
    {
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }
        if (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443) {
            return true;
        }

        return !empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https';
    }

    /** @return array{lifetime:int,path:string,httponly:bool,samesite:string,secure:bool} */
    public function params(): array
    {
        return [
            'lifetime' => 0,
            'path' => $this->config->cookiePath(),
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => $this->isHttps(),
        ];
    }
}
