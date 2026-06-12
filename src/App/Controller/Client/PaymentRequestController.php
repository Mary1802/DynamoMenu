<?php

declare(strict_types=1);

namespace App\Controller\Client;

use App\Core\Application;
use App\Service\ClientPaymentService;

final class PaymentRequestController
{
    private ClientPaymentService $payments;

    public function __construct(?Application $app = null)
    {
        $app ??= Application::getInstance();
        $this->payments = $app->clientPaymentService();
    }

    public function handle(array $post): void
    {
        $this->payments->submitPaymentRequest($post);
    }
}
