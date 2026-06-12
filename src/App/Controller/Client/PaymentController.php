<?php

declare(strict_types=1);

namespace App\Controller\Client;

use App\Core\Application;
use App\Service\ClientPaymentService;

final class PaymentController
{
    private ClientPaymentService $payments;

    public function __construct(?Application $app = null)
    {
        $app ??= Application::getInstance();
        $this->payments = $app->clientPaymentService();
    }

    /** @return array<string, mixed>|null */
    public function show(array $get): ?array
    {
        $numCommande = (int) ($get['commande'] ?? 0);
        $token = trim((string) ($get['token'] ?? ''));

        return $this->payments->paymentPageData($numCommande, $token !== '' ? $token : null);
    }
}
