<?php

require_once __DIR__ . '/schema_upgrade.php';

function notification_ensure(PDO $pdo): void
{
    schema_upgrade($pdo);
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
    notification_ensure($pdo);
    $stmt = $pdo->prepare('
        INSERT INTO notification (id_client, num_commande, canal, type_notification, titre, message, date_envoi)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ');
    $stmt->execute([$idClient, $numCommande, $canal, $type, $titre, $message]);

    return (int) $pdo->lastInsertId();
}

function notification_commande_prete(PDO $pdo, int $numCommande): void
{
    $stmt = $pdo->prepare('
        SELECT c.id_client, c.num_table
        FROM commande c
        WHERE c.num_commande = ?
    ');
    $stmt->execute([$numCommande]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row || empty($row['id_client'])) {
        return;
    }

    $exists = $pdo->prepare("
        SELECT COUNT(*) FROM notification
        WHERE num_commande = ? AND type_notification = 'commande' AND titre LIKE '%prête%'
    ");
    $exists->execute([$numCommande]);
    if ((int) $exists->fetchColumn() > 0) {
        return;
    }

    notification_create(
        $pdo,
        (int) $row['id_client'],
        $numCommande,
        'commande',
        'Commande prête',
        'Votre commande est prête et va vous être apportée à la table ' . ($row['num_table'] ?? '') . '.',
        'in_app'
    );

    // Canal email : journalisé (envoi SMTP à brancher plus tard)
    notification_create(
        $pdo,
        (int) $row['id_client'],
        $numCommande,
        'commande',
        'Commande prête (email)',
        'Notification email enregistrée — configurez l\'envoi SMTP pour l\'activer.',
        'email'
    );
}

function notification_list_for_client(PDO $pdo, int $idClient, ?int $numCommande = null, bool $unreadOnly = false): array
{
    notification_ensure($pdo);
    $sql = 'SELECT * FROM notification WHERE id_client = ?';
    $params = [$idClient];
    if ($numCommande !== null) {
        $sql .= ' AND (num_commande = ? OR num_commande IS NULL)';
        $params[] = $numCommande;
    }
    if ($unreadOnly) {
        $sql .= ' AND lu = 0';
    }
    $sql .= ' ORDER BY date_creation DESC LIMIT 50';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function notification_mark_read(PDO $pdo, int $idNotification): void
{
    $pdo->prepare('UPDATE notification SET lu = 1 WHERE id_notification = ?')->execute([$idNotification]);
}

function notification_mark_read_for_commande(PDO $pdo, int $idClient, int $numCommande): void
{
    $pdo->prepare('UPDATE notification SET lu = 1 WHERE id_client = ? AND num_commande = ?')->execute([$idClient, $numCommande]);
}

function notification_admin_list(PDO $pdo, int $limit = 100): array
{
    notification_ensure($pdo);
    $stmt = $pdo->prepare('
        SELECT n.*, cl.nom_client, cl.prenom_client, cl.email_client
        FROM notification n
        LEFT JOIN client cl ON n.id_client = cl.id_client
        ORDER BY n.date_creation DESC
        LIMIT ?
    ');
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
