<?php

declare(strict_types=1);

namespace App\Controller\Api\Client;

use App\Auth\ClientSessionService;
use App\Core\Application;
use App\Http\ApiResponse;
use App\Model\CommandeStatut;
use App\Repository\CommandeRepository;
use App\Security\OrderAccess;
use PDOException;

final class CommandeStatutController
{
    private ClientSessionService $session;
    private CommandeRepository $commandes;
    private OrderAccess $orderAccess;

    public function __construct(?Application $app = null)
    {
        $app ??= Application::getInstance();
        $this->session = $app->clientSession();
        $this->commandes = $app->commandeRepository();
        $this->orderAccess = $app->orderAccess();
    }

    public function handle(array $get): void
    {
        $this->session->start();

        $num = (int) ($get['commande'] ?? 0);
        if ($num <= 0) {
            ApiResponse::error('Commande invalide', 400);
        }

        try {
            Application::getInstance()->schemaUpgrade()->run();
            $row = $this->commandes->findForStatusApi($num);
        } catch (PDOException) {
            ApiResponse::error('Erreur serveur', 500);
        }

        if ($row === null) {
            ApiResponse::error('Commande introuvable', 404);
        }

        $token = trim((string) ($get['token'] ?? ''));
        if (!$this->orderAccess->canAccess($row, $token !== '' ? $token : null)) {
            ApiResponse::error('Accès refusé', 403);
        }

        $statut = (string) $row['statut'];
        $countdown = $this->commandes->buildCountdownState($row);

        ApiResponse::json([
            'num_commande' => (int) $row['num_commande'],
            'statut' => $statut,
            'statut_label' => CommandeStatut::clientLabel($statut),
            'montant_total' => (float) $row['montant_total'],
            'num_table' => $row['num_table'],
            'pret' => $statut === CommandeStatut::PRETE,
            'livree' => $statut === CommandeStatut::LIVREE,
            'payee' => Application::getInstance()->commandeRepository()->isOrderPaid($num),
            'countdown_active' => $countdown['countdown_active'],
            'prep_started_at' => $countdown['prep_started_at'],
            'prep_total_seconds' => $countdown['prep_total_seconds'],
            'prep_total_minutes' => $countdown['prep_total_minutes'],
            'prep_remaining_seconds' => $countdown['prep_remaining_seconds'],
            'prep_end_unix' => $countdown['prep_end_unix'],
            'server_unix' => $countdown['server_unix'],
            'prep_finished' => $countdown['prep_finished'],
        ]);
    }
}
