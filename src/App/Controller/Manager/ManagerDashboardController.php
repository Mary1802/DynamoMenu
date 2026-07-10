<?php

declare(strict_types=1);

namespace App\Controller\Manager;

use App\Core\Application;
use App\Service\CommandeService;

final class ManagerDashboardController
{
    private Application $app;
    private CommandeService $commandes;

    public function __construct(?Application $app = null)
    {
        $app ??= Application::getInstance();
        $this->app = $app;
        $this->commandes = $app->commandeService();
    }

    public function handlePost(array $post): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        $action = (string) ($post['action'] ?? '');
        $commandeId = (int) ($post['commande_id'] ?? 0);

        if ($commandeId <= 0 || $action === '') {
            return;
        }

        $this->commandes->handleManagerAction($action, $commandeId);
        header('Location: dashboard.php');
        exit;
    }

    /**
     * @return array{
     *   stats: array{prete:int,livree:int},
     *   commandes_pretes: list<array<string,mixed>>,
     *   dashboard_error: ?string,
     *   notif_count: int,
     *   notif_items: list<array<string,mixed>>
     * }
     */
    public function index(): array
    {
        $stats = $this->commandes->managerStats();
        $commandesPretes = [];
        $dashboardError = null;

        try {
            $repo = $this->commandes->repository();
            $commandesPretes = $repo->findReadyForManager(50);
            $this->commandes->attachLines($commandesPretes);
        } catch (\PDOException $e) {
            $dashboardError = 'Impossible de charger les commandes. Vérifiez que la base est à jour via init_db.php ou run_update.php.';
        }

        $notifItems = $this->app->staffNotificationService()->forRole('manager');

        return [
            'stats' => $stats,
            'commandes_pretes' => $commandesPretes,
            'dashboard_error' => $dashboardError,
            'notif_count' => (int) $stats['prete'],
            'notif_items' => $notifItems,
        ];
    }
}
