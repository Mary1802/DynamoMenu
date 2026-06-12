<?php

/** Pont procédural → App\Service\NotificationService */
require_once __DIR__ . '/bootstrap.php';

function notification_ensure(PDO $pdo): void
{
    app()->notificationService()->ensureSchema();
}

function notification_create(
    PDO $pdo,
    ?int $idClient,
    ?int $numCommande,
    string $type,
    string $titre,
    string $message,
    string $canal = 'in_app'
): int {
    return app()->notificationService()->create($idClient, $numCommande, $type, $titre, $message, $canal);
}

function notification_commande_prete(PDO $pdo, int $numCommande): void
{
    app()->notificationService()->notifyCommandePrete($numCommande);
}

function notification_list_for_client(PDO $pdo, int $idClient, ?int $numCommande = null, bool $unreadOnly = false): array
{
    return app()->notificationService()->listForClient($idClient, $numCommande, $unreadOnly);
}

function notification_mark_read(PDO $pdo, int $idNotification): void
{
    app()->notificationService()->markRead($idNotification);
}

function notification_mark_read_for_commande(PDO $pdo, int $idClient, int $numCommande): void
{
    app()->notificationService()->markReadForCommande($idClient, $numCommande);
}

function notification_admin_list(PDO $pdo, int $limit = 100): array
{
    return app()->notificationService()->findForAdmin(null, null, null, $limit);
}
