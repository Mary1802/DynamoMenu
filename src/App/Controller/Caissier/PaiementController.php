<?php

declare(strict_types=1);

namespace App\Controller\Caissier;

use App\Core\Application;
use App\Service\CommandeService;
use App\Service\PaiementService;

final class PaiementController
{
    private Application $app;
    private PaiementService $paiement;
    private CommandeService $commandes;

    public function __construct(?Application $app = null)
    {
        $app ??= Application::getInstance();
        $this->app = $app;
        $this->paiement = $app->paiementService();
        $this->commandes = $app->commandeService();
    }

    /**
     * @return array{
     *   error: string|null,
     *   commandes_a_payer: list<array<string,mixed>>,
     *   commande_details: array<string,mixed>|null,
     *   commande_lignes: list<array<string,mixed>>,
     *   paiements_recents: list<array<string,mixed>>,
     *   demandes_paiement: list<array<string,mixed>>,
     *   stats_jour: array{total_paiements:int, total_ca:float},
     *   dashboard_error: string|null,
     *   notif_items: list<array<string,mixed>>,
     *   notif_count: int
     * }
     */
    public function handle(array $get, array $post): array
    {
        $this->paiement->ensureSchema();

        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($post['payer_commande'])) {
            $result = $this->paiement->processPayment(
                (int) ($post['commande_id'] ?? 0),
                (string) ($post['mode_paiement'] ?? 'especes'),
                (float) ($post['montant_paye'] ?? 0)
            );

            if ($result['success']) {
                header('Location: generer_facture.php?facture=' . $result['num_facture']);
                exit;
            }

            $error = $result['error'] ?? 'Erreur inconnue.';
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($post['annuler_demande'])) {
            $this->paiement->cancelDemande((int) ($post['demande_id'] ?? 0));
            header('Location: paiement.php');
            exit;
        }

        $voirCommande = isset($get['voir_commande']) ? (int) $get['voir_commande'] : null;
        $data = $this->paiement->paymentPageData($voirCommande);

        $commandeLignes = [];
        if ($data['commande_details'] !== null) {
            $num = (int) ($data['commande_details']['num_commande'] ?? 0);
            if ($num > 0) {
                $lines = $this->commandes->repository()->fetchLines($num);
                $commandeLignes = array_map(static fn($l): array => $l->toArray(), $lines);
            }
        }

        $notifItems = $this->app->staffNotificationService()->forRole('caissier');
        $notifCount = count($data['commandes_a_payer']) + count($data['demandes_paiement']);

        return [
            'error' => $error,
            'commandes_a_payer' => $data['commandes_a_payer'],
            'commande_details' => $data['commande_details'],
            'commande_lignes' => $commandeLignes,
            'paiements_recents' => $data['paiements_recents'],
            'demandes_paiement' => $data['demandes_paiement'],
            'stats_jour' => $data['stats_jour'],
            'dashboard_error' => $data['dashboard_error'],
            'notif_items' => $notifItems,
            'notif_count' => $notifCount,
        ];
    }
}
