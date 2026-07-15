<?php

declare(strict_types=1);

namespace App\Service;

use App\Auth\ClientSessionService;
use App\Core\Application;
use App\Model\CommandeStatut;
use App\Repository\CommandeRepository;
use App\Repository\FactureRepository;
use App\Security\OrderAccess;

final class ClientPaymentService
{
    private const VALID_MODES = ['carte', 'especes', 'mobile'];

    /** @return array<string, string> */
    public static function statusLabels(): array
    {
        return [
            CommandeStatut::EN_ATTENTE => '⏳ En attente',
            CommandeStatut::EN_PREPARATION => '🔥 En préparation',
            CommandeStatut::PRETE => '✅ Prête à servir',
            CommandeStatut::LIVREE => '🍽️ Servie',
            CommandeStatut::ANNULEE => '❌ Annulée',
        ];
    }

    /** @return array<string, string> */
    public static function modeIcons(): array
    {
        return [
            'carte' => '💳 Carte bancaire',
            'especes' => '💵 Espèces',
            'mobile' => '📱 Paiement mobile',
        ];
    }

    public function __construct(
        private readonly ClientSessionService $session,
        private readonly CommandeRepository $commandes,
        private readonly FactureRepository $factures,
        private readonly OrderAccess $orderAccess,
    ) {
    }

    public static function fromApp(?Application $app = null): self
    {
        $app ??= Application::getInstance();

        return new self(
            $app->clientSession(),
            $app->commandeRepository(),
            $app->factureRepository(),
            $app->orderAccess()
        );
    }

    /**
     * @return array{
     *   num_commande: int,
     *   commande: array<string,mixed>,
     *   articles: list<array<string,mixed>>,
     *   est_payee: bool
     * }|null
     */
    public function paymentPageData(int $numCommande, ?string $token): ?array
    {
        $this->session->start();

        if ($numCommande <= 0) {
            return null;
        }

        $commande = $this->commandes->findForClientPayment($numCommande);
        if ($commande === null) {
            return null;
        }

        if (!$this->orderAccess->canAccess($commande, $token !== null && $token !== '' ? $token : null)) {
            header('Location: index.php?err=access');
            exit;
        }

        $articles = $this->factures->fetchInvoiceArticles($numCommande);

        return [
            'num_commande' => $numCommande,
            'commande' => $commande,
            'articles' => $articles,
            'est_payee' => !empty($commande['num_facture']),
        ];
    }

    public function submitPaymentRequest(array $post): void
    {
        $this->session->start();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php');
            exit;
        }

        $this->session->verifyPostCsrf();

        $commandeId = (int) ($post['commande_id'] ?? 0);
        $modePaiement = (string) ($post['mode_paiement'] ?? '');
        $montant = $post['montant'] ?? null;

        $redirectBase = 'paiement_client.php?commande=' . urlencode((string) $commandeId);

        if ($commandeId <= 0 || $modePaiement === '' || $montant === null || $montant === '') {
            header('Location: ' . $redirectBase . '&error=missing_data');
            exit;
        }

        if (!in_array($modePaiement, self::VALID_MODES, true)) {
            header('Location: ' . $redirectBase . '&error=invalid_mode');
            exit;
        }

        $commande = $this->commandes->findReadyForPayment($commandeId);
        if ($commande === null) {
            header('Location: ' . $redirectBase . '&error=commande_not_ready');
            exit;
        }

        $this->orderAccess->requireAccess($commande);

        if ($this->factures->hasFacture($commandeId)) {
            header('Location: ' . $redirectBase . '&error=already_paid');
            exit;
        }

        // Indication session uniquement — l'encaissement réel se fait en caisse (facture).
        $_SESSION['demande_paiement'] = [
            'commande_id' => $commandeId,
            'mode_paiement' => $modePaiement,
            'montant' => (float) $montant,
        ];

        header('Location: confirmation_paiement.php?commande=' . urlencode((string) $commandeId));
        exit;
    }

    /**
     * @return array{
     *   commande_id: int,
     *   demande: array{commande_id:int, mode_paiement:string, montant:float},
     *   commande: array<string,mixed>
     * }|null
     */
    public function confirmationData(array $get): ?array
    {
        $this->session->start();

        if (!isset($_SESSION['demande_paiement']) || !is_array($_SESSION['demande_paiement'])) {
            return null;
        }

        $demande = $_SESSION['demande_paiement'];
        $commandeId = (int) ($get['commande'] ?? $demande['commande_id'] ?? 0);

        $commande = $this->commandes->findPaymentConfirmation($commandeId);
        if ($commande === null) {
            return null;
        }

        unset($_SESSION['demande_paiement']);

        return [
            'commande_id' => $commandeId,
            'demande' => $demande,
            'commande' => $commande,
        ];
    }

    /**
     * @return array{num_commande: int, commande: array<string,mixed>}|null
     */
    public function orderSuccessData(array $get): ?array
    {
        $this->session->start();

        if (!isset($_SESSION['commande_confirmee']) || !is_array($_SESSION['commande_confirmee'])) {
            return null;
        }

        $commande = $_SESSION['commande_confirmee'];
        $numCommande = (int) ($get['commande'] ?? $commande['num_commande'] ?? 0);

        return [
            'num_commande' => $numCommande,
            'commande' => $commande,
        ];
    }
}
