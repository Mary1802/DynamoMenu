<?php

declare(strict_types=1);

namespace App\Service;

use App\Auth\StaffAuthService;
use App\Core\Application;
use App\Repository\ContactRepository;
use PDO;
use PDOException;

final class StaffSettingsService
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly ContactRepository $contacts,
        private readonly SchemaUpgradeService $schema,
        private readonly StaffAuthService $auth,
    ) {
    }

    public static function fromApp(?Application $app = null): self
    {
        $app ??= Application::getInstance();

        return new self(
            $app->db(),
            $app->contactRepository(),
            $app->schemaUpgrade(),
            $app->staffAuth()
        );
    }

    public function ensureSchema(): void
    {
        $this->schema->run();
    }

    /** @return array<string, mixed> */
    public function staffAccount(?array $user): array
    {
        $fallback = [
            'nom' => $user['nom'] ?? 'Utilisateur',
            'email' => $user['email'] ?? '',
            'role' => $this->auth->roleLabel((string) ($user['role'] ?? '')),
            'prenom' => '',
            'nom_famille' => '',
        ];

        $id = (int) ($user['user_id'] ?? 0);
        if ($id <= 0) {
            return $fallback;
        }

        try {
            $stmt = $this->pdo->prepare(
                'SELECT nom_employe, prenom_employe, email_employe, role FROM employe WHERE id_employe = ? LIMIT 1'
            );
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                return [
                    'nom' => trim(($row['prenom_employe'] ?? '') . ' ' . ($row['nom_employe'] ?? '')),
                    'prenom' => (string) ($row['prenom_employe'] ?? ''),
                    'nom_famille' => (string) ($row['nom_employe'] ?? ''),
                    'email' => (string) ($row['email_employe'] ?? ''),
                    'role' => $this->auth->roleLabel((string) ($row['role'] ?? $user['role'] ?? '')),
                ];
            }
        } catch (PDOException) {
            // table absente
        }

        return $fallback;
    }

    /** @return array<string, mixed> */
    public function primaryContact(): array
    {
        $list = $this->contacts->listAll();

        return $list[0] ?? $this->contacts->configFallback();
    }

    /** @return list<array<string, mixed>> */
    public function contactList(): array
    {
        return $this->contacts->listAll();
    }
}
