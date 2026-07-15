<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;
use PDOException;

final class ContactRepository extends BaseRepository
{
    public function ensureSchema(): void
    {
        try {
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS contact (
                id_contact INT PRIMARY KEY AUTO_INCREMENT,
                nom VARCHAR(150) NOT NULL,
                adresse VARCHAR(255) NULL,
                telephone VARCHAR(50) NULL,
                whatsapp VARCHAR(50) NULL,
                email VARCHAR(150) NULL,
                actif TINYINT(1) NOT NULL DEFAULT 1
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (PDOException) {
            // ignore
        }

        $this->dropObsoleteHorairesColumn();
    }

    private function dropObsoleteHorairesColumn(): void
    {
        try {
            $stmt = $this->pdo->query("SHOW TABLES LIKE 'contact'");
            if (!$stmt || $stmt->fetchColumn() === false) {
                return;
            }

            $cols = array_column(
                $this->pdo->query('SHOW COLUMNS FROM contact')->fetchAll(PDO::FETCH_ASSOC),
                'Field'
            );
            if (!in_array('horaires', $cols, true)) {
                return;
            }

            // Migration unique : si restaurant_horaires est encore vide, récupérer l'ancien texte.
            try {
                $legacy = $this->pdo->query(
                    "SELECT horaires FROM contact WHERE horaires IS NOT NULL AND TRIM(horaires) <> '' ORDER BY id_contact ASC LIMIT 1"
                )->fetchColumn();
                if (is_string($legacy) && trim($legacy) !== '') {
                    $this->pdo->exec("CREATE TABLE IF NOT EXISTS restaurant_horaires (
                        id INT PRIMARY KEY DEFAULT 1,
                        contenu TEXT NOT NULL,
                        date_modification TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                    $count = (int) $this->pdo->query('SELECT COUNT(*) FROM restaurant_horaires')->fetchColumn();
                    if ($count === 0) {
                        $ins = $this->pdo->prepare('INSERT INTO restaurant_horaires (id, contenu) VALUES (1, ?)');
                        $ins->execute([trim($legacy)]);
                    } else {
                        $current = $this->pdo->query('SELECT contenu FROM restaurant_horaires WHERE id = 1')->fetchColumn();
                        if (!is_string($current) || trim($current) === '') {
                            $upd = $this->pdo->prepare('UPDATE restaurant_horaires SET contenu = ? WHERE id = 1');
                            $upd->execute([trim($legacy)]);
                        }
                    }
                }
            } catch (PDOException) {
                // ignore migration soft-fail
            }

            $this->pdo->exec('ALTER TABLE contact DROP COLUMN horaires');
        } catch (PDOException) {
            // ignore
        }
    }

    /** @return list<array<string, mixed>> */
    public function listAll(): array
    {
        $this->ensureSchema();

        try {
            $stmt = $this->pdo->query("SHOW TABLES LIKE 'contact'");
            if ($stmt && $stmt->fetchColumn() !== false) {
                $stmt = $this->pdo->query(
                    'SELECT id_contact, nom, adresse, telephone, whatsapp, email, actif FROM contact ORDER BY id_contact ASC'
                );

                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (PDOException) {
            // fallback config
        }

        $single = $this->configFallback();

        return $single !== [] ? [$single] : [];
    }

    /** @return array<string, mixed> */
    public function configFallback(): array
    {
        $app = is_file(dirname(__DIR__, 3) . '/config/app.php')
            ? require dirname(__DIR__, 3) . '/config/app.php'
            : [];

        $contacts = is_array($app['contacts'] ?? null) ? $app['contacts'] : [];
        unset($contacts['horaires']);

        return $contacts;
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        $this->ensureSchema();
        $stmt = $this->pdo->prepare(
            'SELECT id_contact, nom, adresse, telephone, whatsapp, email, actif FROM contact WHERE id_contact = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    public function create(
        string $nom,
        string $adresse,
        string $telephone,
        string $whatsapp,
        string $email,
        bool $actif = true
    ): int {
        $this->ensureSchema();
        $stmt = $this->pdo->prepare(
            'INSERT INTO contact (nom, adresse, telephone, whatsapp, email, actif) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$nom, $adresse, $telephone, $whatsapp, $email, $actif ? 1 : 0]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(
        int $id,
        string $nom,
        string $adresse,
        string $telephone,
        string $whatsapp,
        string $email,
        bool $actif = true
    ): void {
        $this->ensureSchema();
        $stmt = $this->pdo->prepare(
            'UPDATE contact SET nom = ?, adresse = ?, telephone = ?, whatsapp = ?, email = ?, actif = ? WHERE id_contact = ?'
        );
        $stmt->execute([$nom, $adresse, $telephone, $whatsapp, $email, $actif ? 1 : 0, $id]);
    }

    public function delete(int $id): void
    {
        $this->ensureSchema();
        $stmt = $this->pdo->prepare('DELETE FROM contact WHERE id_contact = ?');
        $stmt->execute([$id]);
    }
}
