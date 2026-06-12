<?php

declare(strict_types=1);

namespace App\Controller\Api\Client;

use App\Auth\ClientSessionService;
use App\Core\Application;
use App\Http\ApiResponse;
use App\Repository\CommandeRepository;
use App\Security\OrderAccess;
use App\Service\NotificationService;
use Throwable;

final class NotificationsController
{
    private ClientSessionService $session;
    private CommandeRepository $commandes;
    private OrderAccess $orderAccess;
    private NotificationService $notifications;

    public function __construct(?Application $app = null)
    {
        $app ??= Application::getInstance();
        $this->session = $app->clientSession();
        $this->commandes = $app->commandeRepository();
        $this->orderAccess = $app->orderAccess();
        $this->notifications = $app->notificationService();
    }

    public function handle(array $get): void
    {
        $this->session->start();

        $commande = (int) ($get['commande'] ?? 0);
        if ($commande <= 0) {
            ApiResponse::error('Commande invalide', 400);
        }

        try {
            $orderRow = $this->commandes->findAccessRow($commande);
        } catch (Throwable) {
            ApiResponse::error('Erreur serveur', 500);
        }

        if ($orderRow === null) {
            ApiResponse::error('Commande introuvable', 404);
        }

        $token = trim((string) ($get['token'] ?? ''));
        if (!$this->orderAccess->canAccess($orderRow, $token !== '' ? $token : null)) {
            ApiResponse::error('Accès refusé', 403);
        }

        $idClient = (int) ($orderRow['id_client'] ?? 0);
        if ($idClient <= 0) {
            ApiResponse::error('Client introuvable', 404);
        }

        $markRead = isset($get['mark_read']) && $get['mark_read'] === '1';

        try {
            if ($markRead) {
                $this->notifications->markReadForCommande($idClient, $commande);
            }

            $items = $this->notifications->listForClient($idClient, $commande);
            $unread = count(array_filter($items, static fn(array $n): bool => !(int) $n['lu']));

            ApiResponse::json([
                'notifications' => $items,
                'unread_count' => $unread,
            ]);
        } catch (Throwable) {
            ApiResponse::error('Erreur serveur', 500);
        }
    }
}
