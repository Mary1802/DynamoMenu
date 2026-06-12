<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Core\Application;

final class NotificationController
{
    private Application $app;

    public function __construct(?Application $app = null)
    {
        $this->app = $app ?? Application::getInstance();
    }

    /**
     * @param array<string, mixed> $get
     * @param array<string, mixed> $post
     * @return array{message:string,notifications:list<array<string,mixed>>,annee:string,mois:string,recherche:string}
     */
    public function handle(array $get, array $post): array
    {
        $message = '';
        $notifications = $this->app->notificationService();
        $notifications->ensureSchema();
        $log = $this->app->activityLog();

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($post['send_promo'])) {
            $titre = trim((string) ($post['titre'] ?? ''));
            $msg = trim((string) ($post['message'] ?? ''));

            if ($titre !== '' && $msg !== '') {
                $count = $notifications->broadcastPromo($titre, $msg);
                $log->log('promo_broadcast', "Promo envoyée à {$count} clients : {$titre}", 'notifications');
                $message = "Notification promo envoyée à {$count} client(s).";
            }
        }

        $annee = trim((string) ($get['annee'] ?? ''));
        $mois = trim((string) ($get['mois'] ?? ''));
        $recherche = trim((string) ($get['search'] ?? ''));

        return [
            'message' => $message,
            'notifications' => $notifications->findForAdmin(
                $annee !== '' ? $annee : null,
                $mois !== '' ? $mois : null,
                $recherche !== '' ? $recherche : null
            ),
            'annee' => $annee,
            'mois' => $mois,
            'recherche' => $recherche,
        ];
    }
}
