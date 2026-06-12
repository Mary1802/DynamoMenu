<?php

declare(strict_types=1);

namespace App\Controller\Api\Paiement;

use App\Core\Application;
use App\Http\ApiResponse;
use App\Repository\FactureRepository;
use PDOException;

final class PaiementController
{
    private FactureRepository $factures;

    public function __construct(?Application $app = null)
    {
        $app ??= Application::getInstance();
        $this->factures = $app->factureRepository();
    }

    /** @param array<string, mixed> $get */
    public function handle(array $get): void
    {
        $num = (int) ($get['num_commande'] ?? 0);
        if ($num <= 0) {
            ApiResponse::error('Numéro de commande requis', 400);
        }

        try {
            $facture = $this->factures->findByCommande($num);
        } catch (PDOException) {
            ApiResponse::error('Erreur serveur', 500);
        }

        if ($facture === null) {
            ApiResponse::json(['paid' => false, 'num_commande' => $num]);
        }

        ApiResponse::json([
            'paid' => true,
            'num_commande' => $num,
            'num_facture' => (int) $facture['num_facture'],
            'total_paye' => (float) $facture['total_paye'],
            'mode_paiement' => (string) $facture['mode_paiement'],
            'date_facture' => (string) $facture['date_facture'],
        ]);
    }
}
