<?php

declare(strict_types=1);

namespace App\Controller\Api\Menu;

use App\Core\Application;
use App\Http\ApiResponse;
use App\Service\MenuService;
use PDOException;

final class MenuController
{
    private MenuService $menu;

    public function __construct(?Application $app = null)
    {
        $app ??= Application::getInstance();
        $this->menu = $app->menuService();
    }

    public function handle(): void
    {
        try {
            $this->menu->seedStaticItems();
            ApiResponse::json(['items' => $this->menu->buildMenuItems()]);
        } catch (PDOException) {
            ApiResponse::error('Erreur serveur', 500);
        }
    }
}
