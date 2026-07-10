<?php

declare(strict_types=1);

namespace App\Controller\Client;

use App\Core\Application;
use App\Controller\Client\OrderHistoryController;
use App\Model\CommandeStatut;
use App\Repository\ContactRepository;
use App\Service\TableContextService;
use PDOException;

final class HomeController
{
    private TableContextService $tables;
    private ContactRepository $contacts;

    public function __construct(?Application $app = null)
    {
        $app ??= Application::getInstance();
        $this->tables = $app->tableContextService();
        $this->contacts = $app->contactRepository();
    }

    /**
     * @return array{
     *   tableCtx: array<string,mixed>|null,
     *   tableError: string|null,
     *   scanError: bool,
     *   menuUrl: string,
     *   panierUrl: string,
     *   indexUrl: string,
     *   contactRows: list<array<string,mixed>>,
     *   horairesLines: list<string>,
     *   hasContactSection: bool,
     *   recentOrders: list<array<string,mixed>>,
     *   mesCommandesUrl: string
     * }
     */
    public function index(): array
    {
        Application::getInstance()->clientSession()->start();

        try {
            $this->tables->bootstrap();
            $this->tables->redirectAfterScan('index.php');
            Application::getInstance()->clientProfileService()->requireWhenTableBound();
        } catch (PDOException) {
            die('Erreur de connexion');
        }

        $tableCtx = $this->tables->session();
        $tableError = $this->tables->consumeTableError();

        if ($tableCtx !== null && isset($_GET['err'])) {
            header('Location: index.php', true, 302);
            exit;
        }

        $scanError = isset($_GET['err']) && $_GET['err'] === 'table' && $tableCtx === null;

        $contactRows = $this->contacts->listAll();
        if ($contactRows === []) {
            $appConfig = Application::getInstance()->config()->app();
            if (is_array($appConfig['contacts'] ?? null) && $appConfig['contacts'] !== []) {
                $contactRows = [$appConfig['contacts']];
            }
        }

        $orderNums = OrderHistoryController::sessionOrderIds();
        $commandeRepo = Application::getInstance()->commandeRepository();
        Application::getInstance()->schemaUpgrade()->run();
        $recentRows = $commandeRepo->findClientOrderSummaries($orderNums);
        $recentOrders = [];
        foreach (array_slice($recentRows, 0, 3) as $row) {
            $num = (int) $row['num_commande'];
            $statut = (string) $row['statut'];
            $countdown = $commandeRepo->buildCountdownState($row);
            $recentOrders[] = [
                'num_commande' => $num,
                'statut' => $statut,
                'statut_label' => CommandeStatut::clientLabel($statut),
                'statut_class' => match ($statut) {
                    'prete' => 'is-ready',
                    'livree', 'annulee' => 'is-done',
                    default => '',
                },
                'detail_url' => $this->tables->link('suivi_commande.php') . '?commande=' . $num,
                'countdown_active' => $countdown['countdown_active'],
                'prep_end_unix' => $countdown['prep_end_unix'],
                'prep_remaining_seconds' => $countdown['prep_remaining_seconds'],
                'server_unix' => $countdown['server_unix'],
            ];
        }

        $horairesLines = Application::getInstance()->horairesRepository()->lines();

        return [
            'tableCtx' => $tableCtx,
            'tableError' => $tableError,
            'scanError' => $scanError,
            'menuUrl' => $this->tables->link('menu.php'),
            'panierUrl' => $this->tables->link('panier.php'),
            'indexUrl' => $this->tables->link('index.php'),
            'mesCommandesUrl' => $this->tables->link('mes_commandes.php'),
            'contactRows' => $contactRows,
            'horairesLines' => $horairesLines,
            'hasContactSection' => self::hasContactSection($contactRows, $horairesLines),
            'recentOrders' => $recentOrders,
        ];
    }

    /**
     * @param list<array<string, mixed>> $contactRows
     * @param list<string> $horairesLines
     */
    private static function hasContactSection(array $contactRows, array $horairesLines): bool
    {
        if ($horairesLines !== []) {
            return true;
        }

        foreach ($contactRows as $row) {
            $nom = trim((string) ($row['nom'] ?? $row['nom_etablissement'] ?? ''));
            if ($nom !== ''
                || trim((string) ($row['adresse'] ?? '')) !== ''
                || trim((string) ($row['telephone'] ?? '')) !== ''
                || trim((string) ($row['email'] ?? '')) !== ''
                || trim((string) ($row['whatsapp'] ?? '')) !== '') {
                return true;
            }
        }

        return false;
    }
}
