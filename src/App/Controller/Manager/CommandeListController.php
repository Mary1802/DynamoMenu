<?php

declare(strict_types=1);

namespace App\Controller\Manager;

use App\Core\Application;
use App\Model\CommandeStatut;
use App\Service\CommandeService;

final class CommandeListController
{
    private CommandeService $commandes;

    public function __construct(?Application $app = null)
    {
        $app ??= Application::getInstance();
        $this->commandes = $app->commandeService();
    }

    /**
     * @return array{
     *   filtre: string,
     *   q: string,
     *   date: string,
     *   commandes: list<array<string,mixed>>,
     *   commandes_count: int,
     *   statut_labels: array<string,string>
     * }
     */
    public function index(array $get): array
    {
        $filtre = (string) ($get['filtre'] ?? 'toutes');
        $q = trim((string) ($get['q'] ?? ''));
        $date = trim((string) ($get['date'] ?? ''));
        $repo = $this->commandes->repository();

        $commandes = $repo->findForManager(
            $filtre,
            $q !== '' ? $q : null,
            $date !== '' ? $date : null
        );

        $this->commandes->attachLines($commandes);

        return [
            'filtre' => $filtre,
            'q' => $q,
            'date' => $date,
            'commandes' => $commandes,
            'commandes_count' => count($commandes),
            'statut_labels' => CommandeStatut::labels(),
        ];
    }

    public function handlePost(array $post): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        $action = (string) ($post['action'] ?? '');
        $commandeId = (int) ($post['commande_id'] ?? 0);

        if ($commandeId <= 0 || $action === '') {
            return;
        }

        $this->commandes->handleManagerAction($action, $commandeId);

        $redirect = 'commandes.php';
        $params = [];
        if (!empty($post['filtre'])) {
            $params['filtre'] = (string) $post['filtre'];
        }
        if (!empty($post['q'])) {
            $params['q'] = (string) $post['q'];
        }
        if (!empty($post['date'])) {
            $params['date'] = (string) $post['date'];
        }
        if ($params !== []) {
            $redirect .= '?' . http_build_query($params);
        }

        header('Location: ' . $redirect);
        exit;
    }
}
