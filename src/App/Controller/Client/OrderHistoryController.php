<?php

declare(strict_types=1);

namespace App\Controller\Client;

use App\Auth\ClientSessionService;
use App\Core\Application;
use App\Model\CommandeStatut;
use App\Repository\CommandeRepository;
use App\Service\TableContextService;

final class OrderHistoryController
{
    private ClientSessionService $session;
    private TableContextService $tables;
    private CommandeRepository $commandes;

    public function __construct(?Application $app = null)
    {
        $app ??= Application::getInstance();
        $this->session = $app->clientSession();
        $this->tables = $app->tableContextService();
        $this->commandes = $app->commandeRepository();
    }

    /**
     * @return array{
     *   orders: list<array<string,mixed>>,
     *   indexUrl: string,
     *   menuUrl: string
     * }
     */
    public function index(): array
    {
        $this->session->start();
        $this->tables->bootstrap();

        $nums = self::sessionOrderIds();
        $rows = $this->commandes->findClientOrderSummaries($nums);

        $orders = [];
        foreach ($rows as $row) {
            $num = (int) $row['num_commande'];
            $statut = (string) $row['statut'];
            $orders[] = [
                'num_commande' => $num,
                'statut' => $statut,
                'statut_label' => CommandeStatut::clientLabel($statut),
                'date_commande' => (string) ($row['date_commande'] ?? ''),
                'montant_total' => (float) ($row['montant_total'] ?? 0),
                'detail_url' => $this->tables->link('suivi_commande.php') . '?commande=' . $num,
            ];
        }

        return [
            'orders' => $orders,
            'indexUrl' => $this->tables->link('index.php'),
            'menuUrl' => $this->tables->link('menu.php'),
        ];
    }

    /** @return list<int> */
    public static function sessionOrderIds(): array
    {
        $nums = [];
        if (!empty($_SESSION['suivi_commande_id'])) {
            $nums[] = (int) $_SESSION['suivi_commande_id'];
        }
        if (!empty($_SESSION['order_access']) && is_array($_SESSION['order_access'])) {
            foreach ($_SESSION['order_access'] as $n) {
                $n = (int) $n;
                if ($n > 0 && !in_array($n, $nums, true)) {
                    $nums[] = $n;
                }
            }
        }

        return array_reverse($nums);
    }
}
