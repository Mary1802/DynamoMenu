<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Core\Application;
use App\Model\CommandeStatut;
use App\Service\CommandeService;

final class CommandeController
{
    private CommandeService $commandes;

    public function __construct(?Application $app = null)
    {
        $app ??= Application::getInstance();
        $this->commandes = $app->commandeService();
    }

    /**
     * @return array{
     *   message: string,
     *   statuts: array<string,string>,
     *   commandes: list<array<string,mixed>>,
     *   filter: string,
     *   q: string
     * }
     */
    public function handle(array $get, array $post): array
    {
        $message = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($post['update_statut'])) {
            $num = (int) ($post['num_commande'] ?? 0);
            $statut = (string) ($post['statut'] ?? '');
            if ($num > 0 && CommandeStatut::isValid($statut)) {
                $this->commandes->updateStatut($num, $statut);
                $message = 'Statut mis à jour.';
            }
        }

        $filter = (string) ($get['statut'] ?? '');
        $q = trim((string) ($get['q'] ?? ''));

        $statutFilter = ($filter !== '' && CommandeStatut::isValid($filter)) ? $filter : null;
        $commandes = $this->commandes->repository()->findForAdmin(
            $statutFilter,
            $q !== '' ? $q : null
        );

        return [
            'message' => $message,
            'statuts' => CommandeStatut::labels(),
            'commandes' => $commandes,
            'filter' => $filter,
            'q' => $q,
        ];
    }
}
