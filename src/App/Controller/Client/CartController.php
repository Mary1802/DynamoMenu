<?php

declare(strict_types=1);

namespace App\Controller\Client;

use App\Auth\ClientSessionService;
use App\Core\Application;
use App\Http\ApiResponse;
use App\Service\CartService;
use App\Service\TableContextService;
use PDO;

final class CartController
{
    private ClientSessionService $session;
    private CartService $cart;
    private TableContextService $tables;
    private PDO $pdo;

    public function __construct(?Application $app = null)
    {
        $app ??= Application::getInstance();
        $this->session = $app->clientSession();
        $this->cart = $app->cartService();
        $this->tables = $app->tableContextService();
        $this->pdo = $app->db();
    }

    /**
     * @return array{
     *   tableCtx: array<string,mixed>|null,
     *   panier: list<array<string,mixed>>,
     *   total_panier: float,
     *   nombre_articles: int,
     *   tva_rate: float,
     *   tva_amount: float,
     *   total_ttc: float
     * }
     */
    public function handle(array $get, array $post): array
    {
        $this->session->start();
        $this->tables->bootstrap();

        require_once dirname(__DIR__, 4) . '/includes/money.php';
        contient_ensure_schema($this->pdo);

        if (isset($get['action']) && $get['action'] === 'add') {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                ApiResponse::json($this->cart->handleAjaxAdd($post));
            }
            ApiResponse::json(['success' => false, 'message' => 'Méthode non autorisée']);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->session->verifyPostCsrf();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($post['ajouter_au_panier'])) {
            $this->cart->addFromForm($this->pdo, $post);
            header('Location: panier.php');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($post['modifier_quantite'])) {
            $this->cart->modifyQuantity((int) ($post['index'] ?? 0), (string) ($post['action'] ?? ''));
            header('Location: panier.php');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($post['supprimer_article'])) {
            $this->cart->removeItem((int) ($post['index'] ?? 0));
            header('Location: panier.php');
            exit;
        }

        $totals = $this->cart->totals();

        return [
            'tableCtx' => $this->tables->session(),
            'panier' => $totals['panier'],
            'total_panier' => $totals['total_panier'],
            'nombre_articles' => $totals['nombre_articles'],
            'tva_rate' => $totals['tva_rate'],
            'tva_amount' => $totals['tva_amount'],
            'total_ttc' => $totals['total_ttc'],
        ];
    }
}
