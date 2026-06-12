<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\Application;
use App\Repository\CommandeRepository;
use App\Repository\FactureRepository;
use PDO;
use PDOException;
use Throwable;

final class PaiementService
{
    private const VALID_MODES = ['carte', 'especes', 'mobile'];

    public function __construct(
        private readonly PDO $pdo,
        private readonly FactureRepository $factures,
        private readonly CommandeRepository $commandes,
    ) {
    }

    public static function fromApp(?Application $app = null): self
    {
        $app ??= Application::getInstance();

        return new self(
            $app->db(),
            $app->factureRepository(),
            $app->commandeRepository()
        );
    }

    public function ensureSchema(): void
    {
        $this->factures->ensureSchema();
    }

    /**
     * @return array{success:bool, num_facture?:int, error?:string}
     */
    public function processPayment(int $numCommande, string $modePaiement, float $montantPaye): array
    {
        if ($numCommande <= 0 || !in_array($modePaiement, self::VALID_MODES, true)) {
            return ['success' => false, 'error' => 'Données de paiement invalides.'];
        }

        $this->pdo->beginTransaction();

        try {
            $numFacture = $this->factures->create($numCommande, $montantPaye, $modePaiement);
            $this->factures->markDemandesTraitees($numCommande);
            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            return ['success' => false, 'error' => 'Erreur lors du traitement du paiement: ' . $e->getMessage()];
        }

        try {
            Application::getInstance()->fidelityService()->awardAfterPayment($numCommande);
        } catch (Throwable) {
            // Paiement enregistré ; fidélité optionnelle
        }

        return ['success' => true, 'num_facture' => $numFacture];
    }

    public function cancelDemande(int $demandeId): void
    {
        if ($demandeId > 0) {
            $this->factures->cancelDemande($demandeId);
        }
    }

    /**
     * @return array{
     *   commandes_a_payer: list<array<string,mixed>>,
     *   commande_details: array<string,mixed>|null,
     *   paiements_recents: list<array<string,mixed>>,
     *   demandes_paiement: list<array<string,mixed>>,
     *   stats_jour: array{total_paiements:int, total_ca:float},
     *   dashboard_error: string|null
     * }
     */
    public function paymentPageData(?int $voirCommande): array
    {
        $dashboardError = null;
        $commandesAPayer = [];

        try {
            $commandesAPayer = $this->commandes->findAwaitingPaymentDetailed();
        } catch (PDOException $e) {
            $dashboardError = 'Impossible de charger les commandes à payer : ' . $e->getMessage();
        }

        $commandeDetails = null;
        if ($voirCommande !== null && $voirCommande > 0) {
            try {
                $commandeDetails = $this->commandes->findPaymentDetails($voirCommande);
            } catch (PDOException $e) {
                $commandeDetails = null;
                $dashboardError = 'Détails commande : ' . $e->getMessage();
            }
        }

        $paiementsRecents = [];
        try {
            $paiementsRecents = $this->factures->findRecent(5);
        } catch (PDOException) {
            $paiementsRecents = [];
        }

        return [
            'commandes_a_payer' => $commandesAPayer,
            'commande_details' => $commandeDetails,
            'paiements_recents' => $paiementsRecents,
            'demandes_paiement' => $this->factures->findPendingDemandes(),
            'stats_jour' => $this->factures->todayStats(),
            'dashboard_error' => $dashboardError,
        ];
    }

    /**
     * @return array{
     *   commandes_a_encaisser: list<array<string,mixed>>,
     *   commandes_payees: list<array<string,mixed>>,
     *   dashboard_error: string|null
     * }
     */
    public function commandListData(): array
    {
        $dashboardError = null;

        try {
            $awaiting = $this->commandes->findAwaitingPayment();
            $paid = $this->commandes->findRecentlyPaidWithFacture(80);

            return [
                'commandes_a_encaisser' => $awaiting,
                'commandes_payees' => $paid,
                'dashboard_error' => null,
            ];
        } catch (PDOException $e) {
            return [
                'commandes_a_encaisser' => [],
                'commandes_payees' => [],
                'dashboard_error' => 'Impossible de charger les commandes : ' . $e->getMessage(),
            ];
        }
    }
}
