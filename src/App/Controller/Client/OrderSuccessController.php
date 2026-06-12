<?php

declare(strict_types=1);

namespace App\Controller\Client;

use App\Core\Application;
use App\Service\ClientPaymentService;

final class OrderSuccessController
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
        return $this->payments->orderSuccessData($get);
    }
}
