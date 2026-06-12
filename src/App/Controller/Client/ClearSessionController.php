<?php

declare(strict_types=1);

namespace App\Controller\Client;

use App\Core\Application;
use App\Service\CartService;

final class ClearSessionController
{
    private CartService $cart;

    public function __construct(?Application $app = null)
    {
        $app ??= Application::getInstance();
        $this->cart = $app->cartService();
    }

    public function handle(): void
    {
        $this->cart->clearConfirmedOrder();
        echo 'OK';
    }
}
