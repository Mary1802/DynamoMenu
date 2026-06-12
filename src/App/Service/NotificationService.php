<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\Application;
use PDO;

final class NotificationService
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly ?SchemaUpgradeService $schema = null,
    ) {
    }

    public static function fromApp(?Application $app = null): self
    {
        $app ??= Application::getInstance();

        return new self($app->db(), $app->schemaUpgrade());
    }

    public function ensureSchema(): void
    {
        ($this->schema ?? SchemaUpgradeService::fromApp())->run();
    }

    public function create(
        ?int $idClient,
        ?int $numCommande,
        string $type,
        string $titre,
        string $message,
        string $canal = 'in_app'
    ): int {
        $this->ensureSchema();
        $stmt = $this->pdo->prepare('
            INSERT INTO notification (id_client, num_commande, canal, type_notification, titre, message, date_envoi)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ');
        $stmt->execute([$idClient, $numCommande, $canal, $type, $titre, $message]);

        return (int) $this->pdo->lastInsertId();
    }

    public function notifyCommandePrete(int $numCommande): void
    {
        $stmt = $this->pdo->prepare('
            SELECT c.id_client, c.num_table
            FROM commande c
            WHERE c.num_commande = ?
        ');
        $stmt->execute([$numCommande]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || empty($row['id_client'])) {
            return;
        }

        $exists = $this->pdo->prepare("
            SELECT COUNT(*) FROM notification
            WHERE num_commande = ? AND type_notification = 'commande' AND titre LIKE '%prête%'
        ");
        $exists->execute([$numCommande]);
        if ((int) $exists->fetchColumn() > 0) {
            return;
        }

        $idClient = (int) $row['id_client'];
        $table = $row['num_table'] ?? '';

        $this->create(
            $idClient,
            $numCommande,
            'commande',
            'Commande prête',
            'Votre commande est prête et va vous être apportée à la table ' . $table . '.'
        );

        $this->create(
            $idClient,
            $numCommande,
            'commande',
            'Commande prête (email)',
            'Notification email enregistrée — configurez l\'envoi SMTP pour l\'activer.',
            'email'
        );
    }

    /** @return list<array<string, mixed>> */
    public function listForClient(int $idClient, ?int $numCommande = null, bool $unreadOnly = false): array
    {
        $this->ensureSchema();
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

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function markRead(int $idNotification): void
    {
        $this->pdo->prepare('UPDATE notification SET lu = 1 WHERE id_notification = ?')->execute([$idNotification]);
    }

    public function markReadForCommande(int $idClient, int $numCommande): void
    {
        $this->pdo->prepare('UPDATE notification SET lu = 1 WHERE id_client = ? AND num_commande = ?')
            ->execute([$idClient, $numCommande]);
    }

    public function broadcastPromo(string $titre, string $message): int
    {
        $this->ensureSchema();

        $clients = $this->pdo->query(
            'SELECT id_client FROM client WHERE id_client IS NOT NULL'
        )->fetchAll(PDO::FETCH_COLUMN);

        $count = 0;
        foreach ($clients as $idClient) {
            $this->create((int) $idClient, null, 'promo', $titre, $message);
            $count++;
        }

        return $count;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function findForAdmin(?string $annee, ?string $mois, ?string $search, int $limit = 150): array
    {
        $this->ensureSchema();

        $sql = '
            SELECT n.*, cl.nom_client, cl.prenom_client, cl.email_client
            FROM notification n
            LEFT JOIN client cl ON n.id_client = cl.id_client
            WHERE 1=1
        ';
        $params = [];

        if ($annee !== null && $annee !== '' && ctype_digit($annee)) {
            $sql .= ' AND YEAR(n.date_creation) = ?';
            $params[] = $annee;
        }
        if ($mois !== null && $mois !== '' && preg_match('/^\d{1,2}$/', $mois)) {
            $sql .= ' AND MONTH(n.date_creation) = ?';
            $params[] = (int) $mois;
        }
        if ($search !== null && $search !== '') {
            $sql .= ' AND (cl.nom_client LIKE ? OR cl.prenom_client LIKE ?)';
            $pattern = '%' . $search . '%';
            $params[] = $pattern;
            $params[] = $pattern;
        }

        $sql .= ' ORDER BY n.date_creation DESC LIMIT ' . (int) $limit;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
