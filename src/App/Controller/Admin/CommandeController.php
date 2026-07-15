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
     * Liste des commandes en lecture seule (filtre / recherche uniquement).
     *
     * @return array{
     *   statuts: array<string,string>,
     *   commandes: list<array<string,mixed>>,
     *   filter: string,
     *   q: string
     * }
     */
    public function handle(array $get): array
    {
        $filter = (string) ($get['statut'] ?? '');
        $q = trim((string) ($get['q'] ?? ''));

        $statutFilter = ($filter !== '' && CommandeStatut::isValid($filter)) ? $filter : null;
        $commandes = $this->commandes->repository()->findForAdmin(
            $statutFilter,
            $q !== '' ? $q : null
        );

        return [
            'statuts' => CommandeStatut::labels(),
            'commandes' => $commandes,
            'filter' => $filter,
            'q' => $q,
        ];
    }
}
