<?php

declare(strict_types=1);

namespace App\Controller\Client;

use App\Auth\ClientSessionService;
use App\Core\Application;
use App\Model\CommandeStatut;
use App\Repository\CommandeRepository;
use App\Security\OrderAccess;
use App\Service\TableContextService;

final class OrderHistoryController
{
    private ClientSessionService $session;
    private TableContextService $tables;
    private CommandeRepository $commandes;
    private OrderAccess $orderAccess;

    public function __construct(?Application $app = null)
    {
        $app ??= Application::getInstance();
        $this->session = $app->clientSession();
        $this->tables = $app->tableContextService();
        $this->commandes = $app->commandeRepository();
        $this->orderAccess = $app->orderAccess();
    }

    /**
     * @return array{
     *   orders: list<array<string,mixed>>,
     *   orderDetail: array<string,mixed>|null,
     *   selectedCommande: int,
     *   indexUrl: string,
     *   menuUrl: string,
     *   listUrl: string
     * }
     */
    public function index(array $get = []): array
    {
        $this->session->start();
        $this->tables->bootstrap();

        $listUrl = $this->tables->link('mes_commandes.php');
        $selectedNum = (int) ($get['commande'] ?? 0);
        $orderDetail = null;
        if ($selectedNum > 0) {
            $orderDetail = $this->loadOrderDetail(
                $selectedNum,
                trim((string) ($get['token'] ?? ''))
            );
        }

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
                'detail_url' => $listUrl . '?commande=' . $num,
            ];
        }

        return [
            'orders' => $orders,
            'orderDetail' => $orderDetail,
            'selectedCommande' => $selectedNum,
            'indexUrl' => $this->tables->link('index.php'),
            'menuUrl' => $this->tables->link('menu.php'),
            'listUrl' => $listUrl,
        ];
    }

    /**
     * @return array<string,mixed>|null
     */
    private function loadOrderDetail(int $numCommande, string $token): ?array
    {
        $commande = $this->commandes->findForTracking($numCommande);
        if ($commande === null) {
            return null;
        }

        if (!$this->orderAccess->canAccess($commande, $token !== '' ? $token : null)) {
            return null;
        }

        $lignes = $this->commandes->findTrackingLines($numCommande);
        $statut = (string) $commande['statut'];
        $tableLabel = (string) ($commande['table_libelle'] ?? '');
        if ($tableLabel === '') {
            $tableLabel = 'Table ' . (int) ($commande['num_table'] ?? 0);
        }

        return [
            'num_commande' => $numCommande,
            'commande' => $commande,
            'lignes' => $lignes,
            'statut' => $statut,
            'statut_label' => CommandeStatut::clientLabel($statut),
            'date_commande' => (string) ($commande['date_commande'] ?? ''),
            'montant_total' => (float) ($commande['montant_total'] ?? 0),
            'table_label' => $tableLabel,
            'client_nom' => trim(($commande['prenom_client'] ?? '') . ' ' . ($commande['nom_client'] ?? '')),
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
