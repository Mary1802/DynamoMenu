<?php

declare(strict_types=1);

namespace App\Controller\Client;

use App\Core\Application;
use App\Controller\Client\OrderHistoryController;
use App\Model\CommandeStatut;
use App\Service\TableContextService;
use PDOException;

final class HomeController
{
    private TableContextService $tables;

    public function __construct(?Application $app = null)
    {
        $app ??= Application::getInstance();
        $this->tables = $app->tableContextService();
    }

    /**
     * @return array{
     *   tableCtx: array<string,mixed>|null,
     *   tableError: string|null,
     *   tableAccessError: bool,
     *   menuUrl: string,
     *   panierUrl: string,
     *   indexUrl: string,
     *   recentOrders: list<array<string,mixed>>,
     *   mesCommandesUrl: string
     * }
     */
    public function index(): array
    {
        Application::getInstance()->clientSession()->start();

        try {
            $this->tables->bootstrap();
            $this->tables->redirectAfterTableBind('index.php');
        } catch (PDOException) {
            die('Erreur de connexion');
        }

        $tableCtx = $this->tables->session();
        $tableError = $this->tables->consumeTableError();

        if ($tableCtx !== null && isset($_GET['err'])) {
            header('Location: index.php', true, 302);
            exit;
        }

        $tableAccessError = isset($_GET['err']) && $_GET['err'] === 'table' && $tableCtx === null;

        $orderNums = OrderHistoryController::sessionOrderIds();
        $commandeRepo = Application::getInstance()->commandeRepository();
        Application::getInstance()->schemaUpgrade()->run();
        $recentRows = $commandeRepo->findClientOrderSummaries($orderNums);
        $recentOrders = [];
        foreach (array_slice($recentRows, 0, 3) as $row) {
            $num = (int) $row['num_commande'];
            $statut = (string) $row['statut'];
            $countdown = $commandeRepo->buildCountdownState($row);
            $recentOrders[] = [
                'num_commande' => $num,
                'statut' => $statut,
                'statut_label' => CommandeStatut::clientLabel($statut),
                'statut_class' => match ($statut) {
                    'prete' => 'is-ready',
                    'livree', 'annulee' => 'is-done',
                    default => '',
                },
                'detail_url' => $this->tables->link('suivi_commande.php') . '?commande=' . $num,
                'countdown_active' => $countdown['countdown_active'],
                'prep_end_unix' => $countdown['prep_end_unix'],
                'prep_remaining_seconds' => $countdown['prep_remaining_seconds'],
                'server_unix' => $countdown['server_unix'],
            ];
        }

        return [
            'tableCtx' => $tableCtx,
            'tableError' => $tableError,
            'tableAccessError' => $tableAccessError,
            'menuUrl' => $this->tables->link('menu.php'),
            'panierUrl' => $this->tables->link('panier.php'),
            'indexUrl' => $this->tables->link('index.php'),
            'mesCommandesUrl' => $this->tables->link('mes_commandes.php'),
            'recentOrders' => $recentOrders,
        ];
    }
}
