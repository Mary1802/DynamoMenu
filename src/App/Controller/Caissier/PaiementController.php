<?php

declare(strict_types=1);

namespace App\Controller\Caissier;

use App\Core\Application;
use App\Service\CommandeService;
use App\Service\PaiementService;

final class PaiementController
{
    private const SESSION_ENCAISSE_KEY = 'caisse_encaisse';
    private const SESSION_PAY_TOKEN_KEY = 'caisse_pay_token';

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
     *   stats_jour: array{total_paiements:int, total_ca:float},
     *   dashboard_error: string|null,
     *   notif_items: list<array<string,mixed>>,
     *   notif_count: int,
     *   payment_completed: bool,
     *   num_facture_encaisse: int,
     *   mode_paiement_encaisse: string|null,
     *   show_payment_modal: bool,
     *   payment_token: string|null
     * }
     */
    public function handle(array $get, array $post): array
    {
        $this->sendNoCacheHeaders();
        $this->paiement->ensureSchema();

        $error = null;
        $paymentCompleted = false;
        $numFactureEncaisse = 0;
        $voirCommande = null;
        $paymentToken = null;

        if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($get['voir_commande'])) {
            $voirId = (int) $get['voir_commande'];
            if ($voirId > 0 && $this->app->factureRepository()->hasFacture($voirId)) {
                $existing = $this->app->factureRepository()->findByCommande($voirId);
                if ($existing !== null) {
                    $this->redirectToEncaisseSummary($voirId, (int) $existing['num_facture']);
                }
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($post['payer_commande'])) {
            $numCommande = (int) ($post['commande_id'] ?? 0);
            if ($numCommande <= 0) {
                $error = 'Commande invalide.';
            } elseif ($this->app->factureRepository()->hasFacture($numCommande)) {
                $existing = $this->app->factureRepository()->findByCommande($numCommande);
                if ($existing !== null) {
                    $this->redirectToEncaisseSummary($numCommande, (int) $existing['num_facture']);
                }
            } elseif (!$this->verifyPaymentToken($numCommande, (string) ($post['pay_token'] ?? ''))) {
                $error = 'Ce paiement a déjà été enregistré ou la page est obsolète. Fermez la fenêtre et rouvrez la commande depuis la liste.';
                $voirCommande = $numCommande;
            } else {
                $result = $this->paiement->processPayment(
                    $numCommande,
                    (string) ($post['mode_paiement'] ?? 'especes'),
                    (float) ($post['montant_paye'] ?? 0)
                );

                if ($result['success'] && !empty($result['num_facture'])) {
                    $this->invalidatePaymentToken($numCommande);
                    $this->redirectToEncaisseSummary($numCommande, (int) $result['num_facture']);
                }

                $error = $result['error'] ?? 'Erreur inconnue.';
                $voirCommande = $numCommande;
            }
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
            if ($commandeDetails !== null && !$paymentCompleted) {
                $paymentToken = $this->createPaymentToken($voirCommande);
            }
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
        $notifCount = count($data['commandes_a_payer']);

        $showPaymentModal = $commandeDetails !== null
            && ($paymentCompleted || isset($get['voir_commande']) || ($error !== null && $voirCommande !== null));

        return [
            'error' => $error,
            'commandes_a_payer' => $data['commandes_a_payer'],
            'commande_details' => $commandeDetails,
            'commande_lignes' => $commandeLignes,
            'paiements_recents' => $data['paiements_recents'],
            'stats_jour' => $data['stats_jour'],
            'dashboard_error' => $data['dashboard_error'],
            'notif_items' => $notifItems,
            'notif_count' => $notifCount,
            'payment_completed' => $paymentCompleted,
            'num_facture_encaisse' => $numFactureEncaisse,
            'mode_paiement_encaisse' => $modePaiementEncaisse,
            'show_payment_modal' => $showPaymentModal,
            'payment_token' => $paymentToken,
        ];
    }

    private function sendNoCacheHeaders(): void
    {
        if (headers_sent()) {
            return;
        }

        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
    }

    private function createPaymentToken(int $numCommande): string
    {
        $token = bin2hex(random_bytes(16));
        if (!isset($_SESSION[self::SESSION_PAY_TOKEN_KEY]) || !is_array($_SESSION[self::SESSION_PAY_TOKEN_KEY])) {
            $_SESSION[self::SESSION_PAY_TOKEN_KEY] = [];
        }
        $_SESSION[self::SESSION_PAY_TOKEN_KEY][$numCommande] = $token;

        return $token;
    }

    private function verifyPaymentToken(int $numCommande, string $token): bool
    {
        if ($numCommande <= 0 || $token === '') {
            return false;
        }

        $expected = $_SESSION[self::SESSION_PAY_TOKEN_KEY][$numCommande] ?? '';

        return $expected !== '' && hash_equals($expected, $token);
    }

    private function invalidatePaymentToken(int $numCommande): void
    {
        if (isset($_SESSION[self::SESSION_PAY_TOKEN_KEY][$numCommande])) {
            unset($_SESSION[self::SESSION_PAY_TOKEN_KEY][$numCommande]);
        }
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
        $this->invalidatePaymentToken($numCommande);

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
