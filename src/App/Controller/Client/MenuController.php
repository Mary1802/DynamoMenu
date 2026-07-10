<?php

declare(strict_types=1);

namespace App\Controller\Client;

use App\Auth\ClientSessionService;
use App\Core\Application;
use App\Service\MenuService;
use App\Service\TableContextService;
use PDOException;

final class MenuController
{
    private ClientSessionService $session;
    private TableContextService $tables;
    private MenuService $menu;
    private Application $app;

    public function __construct(?Application $app = null)
    {
        $app ??= Application::getInstance();
        $this->app = $app;
        $this->session = $app->clientSession();
        $this->tables = $app->tableContextService();
        $this->menu = $app->menuService();
    }

    /**
     * @return array{
     *   tableCtx: array<string,mixed>|null,
     *   menuItems: list<array<string,mixed>>,
     *   menuImageIndex: array<string,string>,
     *   menuImagePlaceholder: string
     * }
     */
    public function index(): array
    {
        $this->session->start();

        try {
            $this->app->schemaUpgrade()->run();
            $this->tables->bootstrap();
            $this->app->clientProfileService()->requireWhenTableBound();
        } catch (PDOException) {
            die('Erreur de connexion');
        }

        $this->menu->seedStaticItems();

        return [
            'tableCtx' => $this->tables->session(),
            'menuItems' => $this->menu->buildMenuItems(),
            'menuImageIndex' => $this->menu->buildImageIndex(),
            'menuImagePlaceholder' => $this->menu->imagePlaceholder(),
        ];
    }
}
