<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Core\Application;
use App\Service\FidelityService;

final class FideliteController
{
    private Application $app;

    public function __construct(?Application $app = null)
    {
        $this->app = $app ?? Application::getInstance();
    }

    /**
     * @param array<string, mixed> $post
     * @return array{message:string,rewards:list<array<string,mixed>>,types:array<string,string>}
     */
    public function handle(array $post): array
    {
        $message = '';
        $fidelity = $this->app->fidelityService();
        $fidelity->ensureSchema();
        $log = $this->app->activityLog();
        $types = FidelityService::rewardTypeLabels();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($post['add_reward'])) {
                $libelle = trim((string) ($post['libelle'] ?? ''));
                $fidelity->createReward(
                    $libelle,
                    trim((string) ($post['description'] ?? '')),
                    (int) ($post['points_requis'] ?? 0),
                    (string) ($post['type_recompense'] ?? 'pourcentage'),
                    (float) ($post['valeur'] ?? 0)
                );
                $log->log('recompense_create', 'Récompense : ' . $libelle, 'fidelite');
                $message = 'Récompense créée.';
            }

            if (isset($post['update_reward'])) {
                $fidelity->updateReward(
                    (int) ($post['id_recompense'] ?? 0),
                    trim((string) ($post['libelle'] ?? '')),
                    trim((string) ($post['description'] ?? '')),
                    (int) ($post['points_requis'] ?? 0),
                    (string) ($post['type_recompense'] ?? 'pourcentage'),
                    (float) ($post['valeur'] ?? 0),
                    isset($post['actif'])
                );
                $message = 'Récompense mise à jour.';
            }

            if (isset($post['delete_reward'])) {
                $fidelity->deleteReward((int) ($post['id_recompense'] ?? 0));
                $message = 'Récompense supprimée.';
            }
        }

        return [
            'message' => $message,
            'rewards' => $fidelity->listRewards(false),
            'types' => $types,
        ];
    }
}
