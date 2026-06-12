<?php

declare(strict_types=1);

namespace App\Controller\Api\Client;

use App\Core\Application;
use App\Http\ApiResponse;
use App\Service\FidelityService;
use PDOException;

final class ClientProfileController
{
    private FidelityService $fidelity;

    public function __construct(?Application $app = null)
    {
        $app ??= Application::getInstance();
        $this->fidelity = $app->fidelityService();
    }

    /** @param array<string, mixed> $get */
    public function handle(array $get): void
    {
        $email = trim((string) ($get['email'] ?? ''));
        if ($email === '') {
            ApiResponse::error('Email requis', 400);
        }

        try {
            ApiResponse::json($this->fidelity->lookupByEmail($email));
        } catch (PDOException) {
            ApiResponse::error('Erreur serveur', 500);
        }
    }
}
