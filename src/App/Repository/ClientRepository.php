<?php

declare(strict_types=1);

namespace App\Repository;

use App\Service\ClientProfileService;
use PDO;
use PDOException;
use RuntimeException;

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

        // Ancienne fidélité retirée de l'app : nettoyer les colonnes orphelines.
        foreach (['points', 'niveau_fidelite', 'points_fidelite'] as $obsolete) {
            if (in_array($obsolete, $columns, true)) {
                try {
                    $this->pdo->exec('ALTER TABLE client DROP COLUMN `' . str_replace('`', '``', $obsolete) . '`');
                } catch (PDOException) {
                    // ignore
                }
            }
        }

        $this->ensureClientUniqueness();
    }

    /** Normalise les champs et pose des index uniques email / téléphone. */
    private function ensureClientUniqueness(): void
    {
        try {
            $this->pdo->exec('UPDATE client SET email_client = LOWER(TRIM(email_client)) WHERE email_client IS NOT NULL AND email_client <> \'\'');
        } catch (PDOException) {
            // ignore
        }

        try {
            $rows = $this->pdo->query('SELECT id_client, telephone_client FROM client WHERE telephone_client IS NOT NULL')->fetchAll(PDO::FETCH_ASSOC);
            $stmt = $this->pdo->prepare('UPDATE client SET telephone_client = ? WHERE id_client = ?');
            foreach ($rows as $row) {
                $normalized = ClientProfileService::normalizeTelephone((string) ($row['telephone_client'] ?? ''));
                if ($normalized !== '' && $normalized !== (string) $row['telephone_client']) {
                    $stmt->execute([$normalized, (int) $row['id_client']]);
                }
            }
        } catch (PDOException) {
            // ignore
        }

        try {
            $this->pdo->exec('CREATE UNIQUE INDEX uq_client_email ON client (email_client)');
        } catch (PDOException) {
            // ignore si doublons existants ou index déjà là
        }
        try {
            $this->pdo->exec('CREATE UNIQUE INDEX uq_client_telephone ON client (telephone_client)');
        } catch (PDOException) {
            // ignore
        }
    }

    public function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email), 'UTF-8');
    }

    /**
     * Retrouve le client dont l'identité est exactement (e-mail + téléphone),
     * ou une fiche incomplète réutilisable (même e-mail sans tél, ou même tél sans e-mail).
     */
    public function findIdByEmailAndTelephone(string $email, string $telephone): ?int
    {
        $email = $this->normalizeEmail($email);
        $telephone = ClientProfileService::normalizeTelephone($telephone);
        if ($email === '' || $telephone === '') {
            return null;
        }

        $stmt = $this->pdo->prepare(
            'SELECT id_client FROM client
             WHERE LOWER(TRIM(email_client)) = ?
               AND telephone_client = ?
             LIMIT 1'
        );
        $stmt->execute([$email, $telephone]);
        $id = $stmt->fetchColumn();
        if ($id !== false) {
            return (int) $id;
        }

        // Même e-mail, téléphone encore vide → même client (complétion).
        $stmt = $this->pdo->prepare(
            'SELECT id_client FROM client
             WHERE LOWER(TRIM(email_client)) = ?
               AND (telephone_client IS NULL OR TRIM(telephone_client) = \'\')
             LIMIT 1'
        );
        $stmt->execute([$email]);
        $id = $stmt->fetchColumn();
        if ($id !== false) {
            return (int) $id;
        }

        // Même téléphone, e-mail encore vide → même client (complétion).
        $stmt = $this->pdo->prepare(
            'SELECT id_client FROM client
             WHERE telephone_client = ?
               AND (email_client IS NULL OR TRIM(email_client) = \'\')
             LIMIT 1'
        );
        $stmt->execute([$telephone]);
        $id = $stmt->fetchColumn();

        return $id !== false ? (int) $id : null;
    }

    /**
     * Vérifie qu'un e-mail ou un téléphone n'est pas déjà lié à un autre couple identité.
     */
    public function findRegistrationConflict(string $email, string $telephone, ?int $excludeId = null): ?string
    {
        $email = $this->normalizeEmail($email);
        $telephone = ClientProfileService::normalizeTelephone($telephone);

        if ($excludeId === null && $this->findIdByEmailAndTelephone($email, $telephone) !== null) {
            return null;
        }

        $stmt = $this->pdo->prepare(
            'SELECT id_client, telephone_client FROM client WHERE LOWER(TRIM(email_client)) = ? LIMIT 1'
        );
        $stmt->execute([$email]);
        $byEmail = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($byEmail) {
            $id = (int) $byEmail['id_client'];
            if ($excludeId === null || $id !== $excludeId) {
                if ($excludeId !== null) {
                    return 'Cet e-mail est déjà utilisé par un autre client.';
                }
                $existingPhone = ClientProfileService::normalizeTelephone((string) ($byEmail['telephone_client'] ?? ''));
                if ($existingPhone !== '' && $existingPhone !== $telephone) {
                    return 'Cet e-mail est déjà associé à un autre numéro de téléphone.';
                }
            }
        }

        $stmt = $this->pdo->prepare(
            'SELECT id_client, email_client FROM client WHERE telephone_client = ? LIMIT 1'
        );
        $stmt->execute([$telephone]);
        $byPhone = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($byPhone) {
            $id = (int) $byPhone['id_client'];
            if ($excludeId === null || $id !== $excludeId) {
                if ($excludeId !== null) {
                    return 'Ce numéro de téléphone est déjà utilisé par un autre client.';
                }
                $existingEmail = $this->normalizeEmail((string) ($byPhone['email_client'] ?? ''));
                if ($existingEmail !== '' && $existingEmail !== $email) {
                    return 'Ce numéro de téléphone est déjà associé à une autre adresse e-mail.';
                }
            }
        }

        return null;
    }

    /**
     * Enregistre ou met à jour un client selon le couple (e-mail + téléphone).
     *
     * @throws RuntimeException si e-mail ou téléphone déjà utilisés avec une autre identité
     */
    public function upsert(string $nom, string $prenom, string $email, string $telephone): int
    {
        $email = $this->normalizeEmail($email);
        $telephone = ClientProfileService::normalizeTelephone($telephone);

        $conflict = $this->findRegistrationConflict($email, $telephone);
        if ($conflict !== null) {
            throw new RuntimeException($conflict);
        }

        $existingId = $this->findIdByEmailAndTelephone($email, $telephone);

        if ($existingId !== null) {
            $stmt = $this->pdo->prepare(
                'UPDATE client SET nom_client = ?, prenom_client = ?, email_client = ?, telephone_client = ? WHERE id_client = ?'
            );
            $stmt->execute([$nom, $prenom, $email, $telephone, $existingId]);

            return $existingId;
        }

        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO client (nom_client, prenom_client, email_client, telephone_client) VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$nom, $prenom, $email, $telephone]);
        } catch (PDOException $e) {
            throw new RuntimeException(
                'Un client existe déjà avec cet e-mail ou ce numéro de téléphone.',
                0,
                $e
            );
        }

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Mise à jour admin d'une fiche client.
     *
     * @throws RuntimeException en cas de conflit d'identité
     */
    public function updateById(int $idClient, string $nom, string $prenom, string $email, string $telephone): void
    {
        if ($idClient <= 0) {
            throw new RuntimeException('Client invalide.');
        }

        $email = $this->normalizeEmail($email);
        $telephone = ClientProfileService::normalizeTelephone($telephone);

        $conflict = $this->findRegistrationConflict($email, $telephone, $idClient);
        if ($conflict !== null) {
            throw new RuntimeException($conflict);
        }

        $stmt = $this->pdo->prepare(
            'UPDATE client
             SET nom_client = ?, prenom_client = ?, email_client = ?, telephone_client = ?
             WHERE id_client = ?'
        );
        $stmt->execute([$nom, $prenom, $email, $telephone, $idClient]);

        if ($stmt->rowCount() === 0) {
            $check = $this->pdo->prepare('SELECT 1 FROM client WHERE id_client = ?');
            $check->execute([$idClient]);
            if ($check->fetchColumn() === false) {
                throw new RuntimeException('Client introuvable.');
            }
        }
    }

    /**
     * Supprime un client et détache ses éventuelles commandes (id_client → NULL).
     */
    public function delete(int $idClient): void
    {
        if ($idClient <= 0) {
            return;
        }

        try {
            $this->pdo->prepare('UPDATE commande SET id_client = NULL WHERE id_client = ?')->execute([$idClient]);
        } catch (PDOException) {
            // ignore si colonne / table absente
        }

        $stmt = $this->pdo->prepare('DELETE FROM client WHERE id_client = ?');
        $stmt->execute([$idClient]);
    }

    /** @return list<array<string, mixed>> */
    public function findForAdmin(?string $query = null, int $limit = 300): array
    {
        $sql = '
            SELECT id_client, nom_client, prenom_client, email_client, telephone_client, date_inscription
            FROM client
            WHERE 1=1
        ';
        $params = [];

        if ($query !== null && $query !== '') {
            $sql .= ' AND (nom_client LIKE ? OR prenom_client LIKE ? OR email_client LIKE ? OR telephone_client LIKE ?)';
            $pattern = '%' . $query . '%';
            $params = [$pattern, $pattern, $pattern, $pattern];
        }

        $sql .= ' ORDER BY LOWER(TRIM(COALESCE(nom_client, \'\'))) ASC,
                         LOWER(TRIM(COALESCE(prenom_client, \'\'))) ASC
                  LIMIT ' . (int) $limit;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
