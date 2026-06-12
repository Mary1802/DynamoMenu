<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

final class Database
{
    private ?PDO $pdo = null;

    public function __construct(
        private readonly Config $config
    ) {
    }

    public function pdo(): PDO
    {
        if ($this->pdo !== null) {
            return $this->pdo;
        }

        $db = $this->config->database();
        $this->pdo = new PDO(
            'mysql:host=' . ($db['host'] ?? 'localhost') . ';dbname=' . ($db['dbname'] ?? ''),
            (string) ($db['user'] ?? ''),
            (string) ($db['password'] ?? ''),
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        return $this->pdo;
    }
}
