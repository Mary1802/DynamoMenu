<?php

declare(strict_types=1);

namespace App\Controller\Caissier;

use App\Core\Application;
use App\Model\CommandeStatut;
use App\Service\CommandeService;
use App\Service\PaiementService;

final class CommandeListController
{
    private PaiementService $paiement;
    private CommandeService $commandes;

    public function __construct(?Application $app = null)
    {
        $app ??= Application::getInstance();
        $this->paiement = $app->paiementService();
        $this->commandes = $app->commandeService();
    }

    /**
     * @return array{
     *   commandes_a_encaisser: list<array<string,mixed>>,
     *   commandes_payees: list<array<string,mixed>>,
     *   statut_labels: array<string,string>,
     *   dashboard_error: string|null
     * }
     */
    public function index(): array
    {
        $this->paiement->ensureSchema();

        $data = $this->paiement->commandListData();
        $this->commandes->attachLines($data['commandes_a_encaisser']);
        $this->commandes->attachLines($data['commandes_payees']);

        return [
            'commandes_a_encaisser' => $data['commandes_a_encaisser'],
            'commandes_payees' => $data['commandes_payees'],
            'statut_labels' => CommandeStatut::labels(),
            'dashboard_error' => $data['dashboard_error'],
        ];
    }
}
