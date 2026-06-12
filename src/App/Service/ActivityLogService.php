<?php

declare(strict_types=1);

namespace App\Service;

use PDO;
use PDOException;

final class ActivityLogService
{
    public function __construct(
        private readonly PDO $pdo
    ) {
    }

    public function log(string $action, string $description, string $module = 'admin'): void
    {
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO log_activite (action, description, module_concerne) VALUES (?, ?, ?)'
            );
            $stmt->execute([$action, $description, $module]);
        } catch (PDOException $e) {
            // ignore
        }
    }

    /** @return list<array<string, mixed>> */
    public function findRecent(?string $query = null, int $limit = 200): array
    {
        try {
            if ($query !== null && $query !== '') {
                $pattern = '%' . $query . '%';
                $stmt = $this->pdo->prepare(
                    'SELECT * FROM log_activite WHERE action LIKE ? OR module_concerne LIKE ? OR description LIKE ? ORDER BY date_action DESC LIMIT ' . (int) $limit
                );
                $stmt->execute([$pattern, $pattern, $pattern]);

                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            return $this->pdo->query(
                'SELECT * FROM log_activite ORDER BY date_action DESC LIMIT ' . (int) $limit
            )->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException) {
            return [];
        }
    }
}
