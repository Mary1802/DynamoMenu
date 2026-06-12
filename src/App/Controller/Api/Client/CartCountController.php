<?php

declare(strict_types=1);

namespace App\Controller\Api\Client;

use App\Core\Application;
use App\Http\ApiResponse;
use App\Service\CartService;

final class CartCountController
{
    private CartService $cart;

    public function __construct(?Application $app = null)
    {
        $app ??= Application::getInstance();
        $this->cart = $app->cartService();
    }

    public function handle(): void
    {
        Application::getInstance()->clientSession()->start();
        ApiResponse::json($this->cart->countSummary());
    }
}
