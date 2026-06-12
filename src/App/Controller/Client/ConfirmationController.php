<?php

declare(strict_types=1);

namespace App\Controller\Client;

use App\Auth\ClientSessionService;
use App\Core\Application;
use App\Service\CartService;
use App\Service\FidelityService;
use App\Service\OrderCreationService;
use App\Service\TableContextService;

final class ConfirmationController
{
    private ClientSessionService $session;
    private CartService $cart;
    private TableContextService $tables;
    private FidelityService $fidelity;
    private OrderCreationService $orders;

    public function __construct(?Application $app = null)
    {
        $app ??= Application::getInstance();
        $this->session = $app->clientSession();
        $this->cart = $app->cartService();
        $this->tables = $app->tableContextService();
        $this->fidelity = $app->fidelityService();
        $this->orders = $app->orderCreationService();
    }

    /**
     * @return array{
     *   error: string|null,
     *   tableCtx: array<string,mixed>,
     *   panier: list<array<string,mixed>>,
     *   total_panier: float,
     *   tva_amount: float,
     *   total_ttc: float,
     *   recompenses_fidelite: list<array<string,mixed>>
     * }|null null when redirect occurred
     */
    public function handle(array $post): ?array
    {
        $this->session->start();
        $this->tables->bootstrap();
        $this->fidelity->ensureSchema();

        $totals = $this->cart->totals();
        if ($totals['panier'] === []) {
            header('Location: panier.php');
            exit;
        }

        $tableCtx = $this->tables->session();
        if ($tableCtx === null) {
            header('Location: index.php?err=table');
            exit;
        }

        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($post['confirmer_commande'])) {
            $this->session->verifyPostCsrf();

            $result = $this->orders->createFromCheckout(
                $post,
                $totals['panier'],
                (string) $tableCtx['num_table'],
                $totals['total_panier']
            );

            if ($result['success']) {
                $_SESSION['commande_confirmee'] = [
                    'num_commande' => $result['num_commande'],
                    'total' => $result['total_ttc'],
                    'table' => $result['num_table'],
                    'remise' => $result['remise'],
                ];
                $_SESSION['suivi_commande_id'] = $result['num_commande'];
                Application::getInstance()->orderAccess()->grant($result['num_commande']);
                unset($_SESSION['panier']);

                header('Location: suivi_commande.php?commande=' . $result['num_commande']);
                exit;
            }

            $error = $result['error'];
        }

        return [
            'error' => $error,
            'tableCtx' => $tableCtx,
            'panier' => $totals['panier'],
            'total_panier' => $totals['total_panier'],
            'tva_amount' => $totals['tva_amount'],
            'total_ttc' => $totals['total_ttc'],
            'recompenses_fidelite' => Application::getInstance()->fidelityService()->listRewards(),
        ];
    }
}
