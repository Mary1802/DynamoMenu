<?php

declare(strict_types=1);

namespace App\Controller\Client;

use App\Auth\ClientSessionService;
use App\Core\Application;
use App\Model\CommandeStatut;
use App\Repository\CommandeRepository;
use App\Security\OrderAccess;
use App\Service\TableContextService;

final class OrderTrackingController
{
    private ClientSessionService $session;
    private TableContextService $tables;
    private CommandeRepository $commandes;
    private OrderAccess $orderAccess;

    public function __construct(?Application $app = null)
    {
        $app ??= Application::getInstance();
        $this->session = $app->clientSession();
        $this->tables = $app->tableContextService();
        $this->commandes = $app->commandeRepository();
        $this->orderAccess = $app->orderAccess();
    }

    /**
     * @return array{
     *   num_commande: int,
     *   commande: array<string,mixed>,
     *   lignes: list<array<string,mixed>>,
     *   facture: array<string,mixed>|null,
     *   statutInitial: string,
     *   tableLabel: string,
     *   clientNom: string,
     *   modePaiement: string,
     *   indexUrl: string
     * }|null
     */
    public function show(array $get): ?array
    {
        $this->session->start();
        $this->tables->bootstrap();

        $numCommande = (int) ($get['commande'] ?? $_SESSION['suivi_commande_id'] ?? 0);
        if ($numCommande <= 0) {
            $this->tables->redirectToIndex();
        }

        $commande = $this->commandes->findForTracking($numCommande);
        if ($commande === null) {
            $this->tables->redirectToIndex();
        }

        if ($this->commandes->isOrderPaid($numCommande)) {
            if (!empty($_SESSION['suivi_commande_id']) && (int) $_SESSION['suivi_commande_id'] === $numCommande) {
                unset($_SESSION['suivi_commande_id']);
            }
            if (!empty($_SESSION['order_access']) && is_array($_SESSION['order_access'])) {
                $_SESSION['order_access'] = array_values(array_filter(
                    array_map('intval', $_SESSION['order_access']),
                    static fn (int $n): bool => $n > 0 && $n !== $numCommande
                ));
            }
            header('Location: ' . $this->tables->link('index.php'), true, 302);
            exit;
        }

        $accessToken = trim((string) ($get['token'] ?? ''));
        if (!$this->orderAccess->canAccess($commande, $accessToken !== '' ? $accessToken : null)) {
            $this->tables->redirectToIndex();
        }

        $lignes = $this->commandes->findTrackingLines($numCommande);
        $facture = $this->commandes->findFactureForCommande($numCommande);

        $statut = (string) $commande['statut'];
        $statutLabels = CommandeStatut::clientLabels();
        $statutLabels['prete'] = 'Prête — en route vers votre table';
        $statutInitial = $statutLabels[$statut] ?? CommandeStatut::clientLabel($statut);

        $tableLabel = $commande['table_libelle'] ?: ('Table ' . $commande['num_table']);
        $clientNom = trim(($commande['prenom_client'] ?? '') . ' ' . ($commande['nom_client'] ?? ''));

        $modePaiement = '';
        if (!empty($commande['mode_paiement_souhaite'])) {
            $modePaiement = $commande['mode_paiement_souhaite'] === 'mobile_money' ? 'Mobile money' : 'Espèces (cash)';
        }
        if ($facture) {
            $modesFacture = ['especes' => 'Espèces', 'mobile' => 'Mobile money', 'carte' => 'Carte'];
            $modePaiement = $modesFacture[$facture['mode_paiement']] ?? (string) $facture['mode_paiement'];
        }

        Application::getInstance()->schemaUpgrade()->run();
        $countdown = $this->commandes->buildCountdownState($commande);
        $prepEstimeMinutes = $this->commandes->calculateEstimatedPrepMinutes($numCommande);

        return [
            'num_commande' => $numCommande,
            'commande' => $commande,
            'lignes' => $lignes,
            'facture' => $facture,
            'statutInitial' => $statutInitial,
            'tableLabel' => $tableLabel,
            'clientNom' => $clientNom,
            'modePaiement' => $modePaiement,
            'indexUrl' => $this->tables->link('index.php'),
            'mesCommandesUrl' => $this->tables->link('mes_commandes.php'),
            'countdown' => $countdown,
            'prepEstimeMinutes' => $prepEstimeMinutes,
            'statutCode' => $statut,
        ];
    }
}
