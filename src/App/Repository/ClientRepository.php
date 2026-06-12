<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

final class ClientRepository extends BaseRepository
{
    public function ensureSchema(): void
    {
        $columns = array_column(
            $this->pdo->query('SHOW COLUMNS FROM client')->fetchAll(PDO::FETCH_ASSOC),
            'Field'
        );

        if (!in_array('prenom_client', $columns, true)) {
            $this->pdo->exec('ALTER TABLE client ADD COLUMN prenom_client VARCHAR(100) NULL AFTER nom_client');
        }
        if (!in_array('email_client', $columns, true)) {
            $this->pdo->exec('ALTER TABLE client ADD COLUMN email_client VARCHAR(100) NULL AFTER prenom_client');
        }
        if (!in_array('telephone_client', $columns, true)) {
            $this->pdo->exec('ALTER TABLE client ADD COLUMN telephone_client VARCHAR(20) NULL AFTER email_client');
        }
    }

    public function findIdByEmail(string $email): ?int
    {
        $stmt = $this->pdo->prepare('SELECT id_client FROM client WHERE email_client = ?');
        $stmt->execute([$email]);
        $id = $stmt->fetchColumn();

        return $id !== false ? (int) $id : null;
    }

    public function upsert(string $nom, string $prenom, string $email, string $telephone): int
    {
        $existingId = $this->findIdByEmail($email);

        if ($existingId !== null) {
            $stmt = $this->pdo->prepare(
                'UPDATE client SET nom_client = ?, prenom_client = ?, telephone_client = ? WHERE id_client = ?'
            );
            $stmt->execute([$nom, $prenom, $telephone, $existingId]);

            return $existingId;
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO client (nom_client, prenom_client, email_client, telephone_client) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$nom, $prenom, $email, $telephone]);

        return (int) $this->pdo->lastInsertId();
    }

    /** @return list<array<string, mixed>> */
    public function findWithFidelity(?string $query = null, int $limit = 300): array
    {
        $sql = '
            SELECT id_client, nom_client, prenom_client, email_client, telephone_client,
                   points, niveau_fidelite, date_inscription
            FROM client
            WHERE 1=1
        ';
        $params = [];

        if ($query !== null && $query !== '') {
            $sql .= ' AND (nom_client LIKE ? OR prenom_client LIKE ? OR email_client LIKE ?)';
            $pattern = '%' . $query . '%';
            $params = [$pattern, $pattern, $pattern];
        }

        $sql .= ' ORDER BY points DESC, nom_client LIMIT ' . (int) $limit;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
