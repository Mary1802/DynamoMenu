<?php

declare(strict_types=1);

namespace App\Controller\Client;

use App\Core\Application;
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
     *   hasContactSection: bool
     * }
     */
    public function index(): array
    {
        Application::getInstance()->clientSession()->start();

        try {
            $this->tables->bootstrap();
            $this->tables->redirectAfterScan('index.php');
        } catch (PDOException) {
            die('Erreur de connexion');
        }

        $tableCtx = $this->tables->session();
        $tableError = $this->tables->consumeTableError();
        $scanError = isset($_GET['err']) && $_GET['err'] === 'table';

        $contactRows = $this->contacts->listAll();
        if ($contactRows === []) {
            $appConfig = Application::getInstance()->config()->app();
            if (is_array($appConfig['contacts'] ?? null) && $appConfig['contacts'] !== []) {
                $contactRows = [$appConfig['contacts']];
            }
        }

        return [
            'tableCtx' => $tableCtx,
            'tableError' => $tableError,
            'scanError' => $scanError,
            'menuUrl' => $this->tables->link('menu.php'),
            'panierUrl' => $this->tables->link('panier.php'),
            'indexUrl' => $this->tables->link('index.php'),
            'contactRows' => $contactRows,
            'hasContactSection' => self::hasContactSection($contactRows),
        ];
    }

    /** @param list<array<string, mixed>> $contactRows */
    private static function hasContactSection(array $contactRows): bool
    {
        foreach ($contactRows as $row) {
            $nom = trim((string) ($row['nom'] ?? $row['nom_etablissement'] ?? ''));
            if ($nom !== ''
                || trim((string) ($row['adresse'] ?? '')) !== ''
                || trim((string) ($row['horaires'] ?? '')) !== ''
                || trim((string) ($row['telephone'] ?? '')) !== ''
                || trim((string) ($row['email'] ?? '')) !== ''
                || trim((string) ($row['whatsapp'] ?? '')) !== '') {
                return true;
            }
        }

        return false;
    }
}
