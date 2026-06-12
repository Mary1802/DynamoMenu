<?php

declare(strict_types=1);

namespace App\Controller\Api\Stats;

use App\Auth\StaffAuthService;
use App\Core\Application;
use App\Http\ApiResponse;
use App\Repository\AdminStatsRepository;
use PDOException;

final class StatsController
{
    private StaffAuthService $auth;
    private AdminStatsRepository $stats;

    public function __construct(?Application $app = null)
    {
        $app ??= Application::getInstance();
        $this->auth = $app->staffAuth();
        $this->stats = $app->adminStatsRepository();
    }

    public function handle(): void
    {
        $user = $this->auth->user();
        if ($user === null || $user['role'] !== 'admin') {
            ApiResponse::error('Accès refusé', 403);
        }

        $jour = date('Y-m-d');
        $mois = date('Y-m');

        try {
            $caJour = $this->stats->salesTotals('day', $jour);
            $caMois = $this->stats->salesTotals('month', $mois);
            ApiResponse::json([
                'stats' => $this->stats->dashboardStats((float) $caJour['ca'], (float) $caMois['ca']),
                'ca_jour' => $caJour,
                'ca_mois' => $caMois,
            ]);
        } catch (PDOException) {
            ApiResponse::error('Erreur serveur', 500);
        }
    }
}
