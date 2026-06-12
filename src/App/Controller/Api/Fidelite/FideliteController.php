<?php

declare(strict_types=1);

namespace App\Controller\Api\Fidelite;

use App\Auth\ClientSessionService;
use App\Core\Application;
use App\Http\ApiResponse;
use App\Service\FidelityService;
use Throwable;

final class FideliteController
{
    private ClientSessionService $session;
    private FidelityService $fidelity;

    public function __construct(?Application $app = null)
    {
        $app ??= Application::getInstance();
        $this->session = $app->clientSession();
        $this->fidelity = $app->fidelityService();
    }

    /** @param array<string, mixed> $get @param array<string, mixed> $post */
    public function handle(array $get, array $post): void
    {
        $this->session->start();

        if (!Application::getInstance()->tableContextService()->session()) {
            ApiResponse::error('Session table requise', 403);
        }

        $email = trim((string) ($get['email'] ?? $post['email'] ?? ''));
        if ($email === '') {
            ApiResponse::error('Email requis', 400);
        }

        try {
            ApiResponse::json($this->fidelity->lookupByEmail($email));
        } catch (Throwable) {
            ApiResponse::error('Erreur serveur', 500);
        }
    }
}
