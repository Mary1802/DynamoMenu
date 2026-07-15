<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;
use PDOException;

final class HorairesRepository extends BaseRepository
{
    public function ensureTable(): void
    {
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS restaurant_horaires (
            id INT PRIMARY KEY DEFAULT 1,
            contenu TEXT NOT NULL,
            date_modification TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $count = (int) $this->pdo->query('SELECT COUNT(*) FROM restaurant_horaires')->fetchColumn();
        if ($count === 0) {
            $stmt = $this->pdo->prepare('INSERT INTO restaurant_horaires (id, contenu) VALUES (1, ?)');
            $stmt->execute([$this->configFallback()]);
        }
    }

    public function get(): string
    {
        try {
            $this->ensureTable();
            $stmt = $this->pdo->query('SELECT contenu FROM restaurant_horaires WHERE id = 1 LIMIT 1');
            $value = $stmt ? $stmt->fetchColumn() : false;

            return is_string($value) ? trim($value) : '';
        } catch (PDOException) {
            return $this->configFallback();
        }
    }

    public function save(string $contenu): void
    {
        $this->ensureTable();
        $stmt = $this->pdo->prepare(
            'INSERT INTO restaurant_horaires (id, contenu) VALUES (1, ?)
             ON DUPLICATE KEY UPDATE contenu = VALUES(contenu)'
        );
        $stmt->execute([trim($contenu)]);
    }

    /** @return list<string> */
    public function lines(): array
    {
        return self::toLines($this->get());
    }

    /** @return list<string> */
    public static function toLines(string $horaires): array
    {
        $horaires = trim($horaires);
        if ($horaires === '') {
            return [];
        }

        $lines = [];
        foreach (preg_split('/[\n;|]+/', $horaires) as $line) {
            $line = trim($line);
            if ($line !== '') {
                $lines[] = $line;
            }
        }

        return $lines !== [] ? $lines : [$horaires];
    }

    /** Valeur initiale depuis config/app.php uniquement. */
    private function configFallback(): string
    {
        $app = is_file(dirname(__DIR__, 3) . '/config/app.php')
            ? require dirname(__DIR__, 3) . '/config/app.php'
            : [];

        return trim((string) (($app['horaires'] ?? $app['contacts']['horaires'] ?? '') ?: ''));
    }
}
