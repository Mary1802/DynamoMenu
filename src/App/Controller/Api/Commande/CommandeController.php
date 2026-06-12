<?php

declare(strict_types=1);

namespace App\Controller\Api\Commande;

use App\Core\Application;
use App\Http\ApiResponse;
use App\Model\CommandeStatut;
use App\Repository\CommandeRepository;
use PDOException;

final class CommandeController
{
    private CommandeRepository $commandes;

    public function __construct(?Application $app = null)
    {
        $app ??= Application::getInstance();
        $this->commandes = $app->commandeRepository();
    }

    /** @param array<string, mixed> $get */
    public function handle(array $get): void
    {
        $num = (int) ($get['num_commande'] ?? $get['commande'] ?? 0);
        if ($num <= 0) {
            ApiResponse::error('Numéro de commande requis', 400);
        }

        try {
            $row = $this->commandes->findForStatusApi($num);
        } catch (PDOException) {
            ApiResponse::error('Erreur serveur', 500);
        }

        if ($row === null) {
            ApiResponse::error('Commande introuvable', 404);
        }

        $statut = (string) $row['statut'];

        ApiResponse::json([
            'num_commande' => (int) $row['num_commande'],
            'statut' => $statut,
            'statut_label' => CommandeStatut::clientLabel($statut),
            'montant_total' => (float) $row['montant_total'],
            'num_table' => $row['num_table'],
        ]);
    }
}
