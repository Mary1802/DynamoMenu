<?php

declare(strict_types=1);

namespace App\Setup;

use App\Core\Application;
use App\Http\StaffPage;

/** Contrôle d'accès aux scripts d'installation / migration (CLI ou admin web). */
final class SetupGuard
{
    public function __construct(
        private readonly Application $app,
    ) {
    }

    public static function fromApp(?Application $app = null): self
    {
        return new self($app ?? Application::getInstance());
    }

    public function isAllowed(): bool
    {
        if (PHP_SAPI === 'cli') {
            return true;
        }

        if (!$this->app->config()->allowWebSetup()) {
            return false;
        }

        $this->app->staffAuth()->startSession();
        $user = StaffPage::user();

        return $user !== null && ($user['role'] ?? '') === 'admin';
    }

    public function requireAccess(): void
    {
        if ($this->isAllowed()) {
            return;
        }

        if (PHP_SAPI === 'cli') {
            fwrite(STDERR, "Accès refusé.\n");
            exit(1);
        }

        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Accès refusé. Connectez-vous en tant qu\'administrateur ou exécutez ce script en CLI.';
        exit;
    }
}
