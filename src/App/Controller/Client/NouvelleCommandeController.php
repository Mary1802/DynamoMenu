<?php

declare(strict_types=1);

namespace App\Controller\Client;

use App\Core\Application;
use App\Service\CartService;
use App\Service\TableContextService;
use PDOException;

final class NouvelleCommandeController
{
    private CartService $cart;
    private TableContextService $tables;

    public function __construct(?Application $app = null)
    {
        $app ??= Application::getInstance();
        $this->cart = $app->cartService();
        $this->tables = $app->tableContextService();
    }

    public function handle(): void
    {
        Application::getInstance()->clientSession()->start();

        try {
            $this->tables->bootstrap();
        } catch (PDOException) {
            // Table optionnelle : on vide quand même le panier
        }

        Application::getInstance()->clientProfileService()->clear();
        $this->cart->clearCart();
        header('Location: ' . $this->tables->link('menu.php'));
        exit;
    }
}
