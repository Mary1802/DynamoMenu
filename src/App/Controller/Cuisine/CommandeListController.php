<?php

declare(strict_types=1);

namespace App\Controller\Cuisine;

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
     *   commandes: list<array<string,mixed>>,
     *   commandes_recentes: list<array<string,mixed>>,
     *   statut_labels: array<string,string>
     * }
     */
    public function index(array $get): array
    {
        $filtre = (string) ($get['filtre'] ?? 'actives');
        $repo = $this->commandes->repository();

        $commandes = $repo->findForCuisine($filtre);
        $commandesRecentes = $repo->findRecentForCuisine(20);

        $this->commandes->attachLines($commandes);
        $this->commandes->attachLines($commandesRecentes);

        return [
            'filtre' => $filtre,
            'commandes' => $commandes,
            'commandes_recentes' => $commandesRecentes,
            'statut_labels' => CommandeStatut::labels(),
        ];
    }
}
