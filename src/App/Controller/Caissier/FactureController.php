<?php

declare(strict_types=1);

namespace App\Controller\Caissier;

use App\Core\Application;
use App\Repository\FactureRepository;

final class FactureController
{
    private FactureRepository $factures;

    public function __construct(?Application $app = null)
    {
        $app ??= Application::getInstance();
        $this->factures = $app->factureRepository();
    }

    /**
     * @return array{
     *   num_facture: int,
     *   facture: array<string,mixed>,
     *   articles: list<array<string,mixed>>,
     *   ht: float,
     *   tva: float
     * }|null
     */
    public function show(array $get): ?array
    {
        $numFacture = (int) ($get['facture'] ?? 0);
        if ($numFacture <= 0) {
            return null;
        }

        $facture = $this->factures->findWithDetails($numFacture);
        if ($facture === null) {
            return null;
        }

        $articles = $this->factures->fetchInvoiceArticles((int) $facture['num_commande']);
        $totalPaye = (float) $facture['total_paye'];
        $tva = $totalPaye * 0.20;
        $ht = $totalPaye - $tva;

        return [
            'num_facture' => $numFacture,
            'facture' => $facture,
            'articles' => $articles,
            'ht' => $ht,
            'tva' => $tva,
        ];
    }
}
