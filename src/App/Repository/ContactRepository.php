<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;
use PDOException;

final class ContactRepository extends BaseRepository
{
    /** @return list<array<string, mixed>> */
    public function listAll(): array
    {
        try {
            $stmt = $this->pdo->query("SHOW TABLES LIKE 'contact'");
            if ($stmt && $stmt->fetchColumn() !== false) {
                $stmt = $this->pdo->query('SELECT * FROM contact ORDER BY id_contact ASC');

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

        return is_array($app['contacts'] ?? null) ? $app['contacts'] : [];
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM contact WHERE id_contact = ?');
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
        string $horaires,
        bool $actif = true
    ): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO contact (nom, adresse, telephone, whatsapp, email, horaires, actif) VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$nom, $adresse, $telephone, $whatsapp, $email, $horaires, $actif ? 1 : 0]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(
        int $id,
        string $nom,
        string $adresse,
        string $telephone,
        string $whatsapp,
        string $email,
        string $horaires,
        bool $actif = true
    ): void {
        $stmt = $this->pdo->prepare(
            'UPDATE contact SET nom = ?, adresse = ?, telephone = ?, whatsapp = ?, email = ?, horaires = ?, actif = ? WHERE id_contact = ?'
        );
        $stmt->execute([$nom, $adresse, $telephone, $whatsapp, $email, $horaires, $actif ? 1 : 0, $id]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM contact WHERE id_contact = ?');
        $stmt->execute([$id]);
    }
}
