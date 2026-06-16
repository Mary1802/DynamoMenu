<?php

declare(strict_types=1);

namespace App\Controller\Caissier;

use App\Core\Application;
use App\Service\CommandeService;
use App\Service\PaiementService;

final class PaiementController
{
    private const SESSION_ENCAISSE_KEY = 'caisse_encaisse';

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
     *   notif_count: int,
     *   payment_completed: bool,
     *   num_facture_encaisse: int,
     *   mode_paiement_encaisse: string|null,
     *   show_payment_modal: bool
     * }
     */
    public function handle(array $get, array $post): array
    {
        $this->paiement->ensureSchema();

        $error = null;
        $paymentCompleted = false;
        $numFactureEncaisse = 0;
        $voirCommande = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($post['payer_commande'])) {
            $numCommande = (int) ($post['commande_id'] ?? 0);
            if ($numCommande <= 0) {
                $error = 'Commande invalide.';
            } else {
                $result = $this->paiement->processPayment(
                    $numCommande,
                    (string) ($post['mode_paiement'] ?? 'especes'),
                    (float) ($post['montant_paye'] ?? 0)
                );

                if ($result['success'] && !empty($result['num_facture'])) {
                    $this->redirectToEncaisseSummary($numCommande, (int) $result['num_facture']);
                }

                $error = $result['error'] ?? 'Erreur inconnue.';
                $voirCommande = $numCommande;
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($post['annuler_demande'])) {
            $this->paiement->cancelDemande((int) ($post['demande_id'] ?? 0));
            header('Location: paiement.php', true, 303);
            exit;
        }

        $flash = $this->consumeEncaisseFlash();
        if ($flash !== null) {
            $paymentCompleted = true;
            $voirCommande = (int) ($flash['commande'] ?? 0);
            $numFactureEncaisse = (int) ($flash['facture'] ?? 0);
        } elseif (!empty($get['encaisse'])) {
            $paymentCompleted = true;
            $voirCommande = (int) ($get['commande'] ?? 0);
            $numFactureEncaisse = (int) ($get['facture'] ?? 0);
        } elseif (isset($get['voir_commande'])) {
            $voirCommande = (int) $get['voir_commande'];
            if ($voirCommande > 0 && $this->app->factureRepository()->hasFacture($voirCommande)) {
                $existing = $this->app->factureRepository()->findByCommande($voirCommande);
                if ($existing !== null) {
                    $this->redirectToEncaisseSummary($voirCommande, (int) $existing['num_facture']);
                }
            }
        }

        if ($paymentCompleted && $voirCommande <= 0) {
            header('Location: paiement.php', true, 303);
            exit;
        }

        $data = $this->paiement->paymentPageData($paymentCompleted ? null : $voirCommande);

        $commandeDetails = null;
        $commandeLignes = [];
        $modePaiementEncaisse = null;

        if ($paymentCompleted && $voirCommande > 0) {
            $commandeDetails = $this->commandes->repository()->findReceiptDetails($voirCommande);
            $facture = $this->app->factureRepository()->findByCommande($voirCommande);
            if ($facture !== null) {
                $modePaiementEncaisse = (string) ($facture['mode_paiement'] ?? '');
                if ($numFactureEncaisse <= 0) {
                    $numFactureEncaisse = (int) ($facture['num_facture'] ?? 0);
                }
            }
            if ($commandeDetails === null || $numFactureEncaisse <= 0) {
                header('Location: paiement.php', true, 303);
                exit;
            }
        } elseif ($voirCommande !== null && $voirCommande > 0) {
            $commandeDetails = $this->commandes->repository()->findPaymentDetails($voirCommande)
                ?? $data['commande_details'];
        } elseif ($data['commande_details'] !== null) {
            $commandeDetails = $data['commande_details'];
        }

        if ($commandeDetails !== null) {
            $num = (int) ($commandeDetails['num_commande'] ?? 0);
            if ($num > 0) {
                $lines = $this->commandes->repository()->fetchLines($num);
                $commandeLignes = array_map(static fn($l): array => $l->toArray(), $lines);
            }
        }

        $notifItems = $this->app->staffNotificationService()->forRole('caissier');
        $notifCount = count($data['commandes_a_payer']) + count($data['demandes_paiement']);

        $showPaymentModal = $commandeDetails !== null
            && ($paymentCompleted || isset($get['voir_commande']) || ($error !== null && $voirCommande !== null));

        return [
            'error' => $error,
            'commandes_a_payer' => $data['commandes_a_payer'],
            'commande_details' => $commandeDetails,
            'commande_lignes' => $commandeLignes,
            'paiements_recents' => $data['paiements_recents'],
            'demandes_paiement' => $data['demandes_paiement'],
            'stats_jour' => $data['stats_jour'],
            'dashboard_error' => $data['dashboard_error'],
            'notif_items' => $notifItems,
            'notif_count' => $notifCount,
            'payment_completed' => $paymentCompleted,
            'num_facture_encaisse' => $numFactureEncaisse,
            'mode_paiement_encaisse' => $modePaiementEncaisse,
            'show_payment_modal' => $showPaymentModal,
        ];
    }

    /** @return array{commande:int,facture:int}|null */
    private function consumeEncaisseFlash(): ?array
    {
        if (empty($_SESSION[self::SESSION_ENCAISSE_KEY]) || !is_array($_SESSION[self::SESSION_ENCAISSE_KEY])) {
            return null;
        }

        $flash = $_SESSION[self::SESSION_ENCAISSE_KEY];
        unset($_SESSION[self::SESSION_ENCAISSE_KEY]);

        return [
            'commande' => (int) ($flash['commande'] ?? 0),
            'facture' => (int) ($flash['facture'] ?? 0),
        ];
    }

    private function redirectToEncaisseSummary(int $numCommande, int $numFacture): never
    {
        $_SESSION[self::SESSION_ENCAISSE_KEY] = [
            'commande' => $numCommande,
            'facture' => $numFacture,
        ];

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        header('Location: paiement.php', true, 303);
        exit;
    }
}
