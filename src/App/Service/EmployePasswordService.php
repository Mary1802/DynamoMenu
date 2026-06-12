<?php

declare(strict_types=1);

namespace App\Service;

use App\Security\PasswordHasher;
use PDO;
use PDOException;

final class EmployePasswordService
{
    /** @var array<string, string> */
    private const KNOWN_PASSWORDS = [
        'admin@dynamomenu.fr' => 'admin123',
    ];

    public function __construct(
        private readonly PDO $pdo,
        private readonly PasswordHasher $hasher
    ) {
    }

    public function ensureColumn(): void
    {
        try {
            $columns = array_column(
                $this->pdo->query('SHOW COLUMNS FROM employe')->fetchAll(PDO::FETCH_ASSOC),
                'Field'
            );

            if (!in_array('mot_de_passe', $columns, true)) {
                return;
            }

            $col = $this->pdo->query("SHOW COLUMNS FROM employe LIKE 'mot_de_passe'")->fetch(PDO::FETCH_ASSOC);
            if ($col && preg_match('/varchar\((\d+)\)/i', (string) ($col['Type'] ?? ''), $match)) {
                if ((int) $match[1] < 255) {
                    $this->pdo->exec('ALTER TABLE employe MODIFY mot_de_passe VARCHAR(255) NOT NULL');
                }
            }

            if (!in_array('mot_de_passe_note', $columns, true)) {
                $this->pdo->exec(
                    'ALTER TABLE employe ADD COLUMN mot_de_passe_note VARCHAR(255) NULL AFTER mot_de_passe'
                );
            }
        } catch (PDOException $e) {
            // ignore
        }
    }

    public function syncPasswordNotes(): void
    {
        $this->ensureColumn();
        $this->upgradePlaintextPasswords();

        try {
            $rows = $this->pdo->query(
                'SELECT id_employe, email_employe, mot_de_passe, mot_de_passe_note FROM employe'
            )->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return;
        }

        $update = $this->pdo->prepare('UPDATE employe SET mot_de_passe_note = ? WHERE id_employe = ?');

        foreach ($rows as $row) {
            if (trim((string) ($row['mot_de_passe_note'] ?? '')) !== '') {
                continue;
            }

            $stored = trim((string) ($row['mot_de_passe'] ?? ''));
            $plain = null;

            if ($stored !== '' && !$this->hasher->isHashed($stored)) {
                $plain = $stored;
            } else {
                $email = strtolower(trim((string) ($row['email_employe'] ?? '')));
                $plain = self::KNOWN_PASSWORDS[$email] ?? null;
            }

            if ($plain !== null) {
                $update->execute([$plain, (int) $row['id_employe']]);
            }
        }
    }

    public function displayPassword(string $email, ?string $passwordNote, ?string $storedHash): ?string
    {
        if ($passwordNote !== null && trim($passwordNote) !== '') {
            return $passwordNote;
        }

        $stored = trim((string) $storedHash);
        if ($stored !== '' && !$this->hasher->isHashed($stored)) {
            return $stored;
        }

        $known = self::KNOWN_PASSWORDS[strtolower(trim($email))] ?? null;

        return $known !== null && $known !== '' ? $known : null;
    }

    public function upgradePlaintextPasswords(): void
    {
        $this->ensureColumn();

        try {
            $rows = $this->pdo->query('SELECT id_employe, mot_de_passe FROM employe')->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return;
        }

        $update = $this->pdo->prepare('UPDATE employe SET mot_de_passe = ? WHERE id_employe = ?');
        $preserveNote = $this->pdo->prepare(
            'UPDATE employe SET mot_de_passe_note = ? WHERE id_employe = ? AND (mot_de_passe_note IS NULL OR mot_de_passe_note = \'\')'
        );

        foreach ($rows as $row) {
            $stored = trim((string) ($row['mot_de_passe'] ?? ''));
            if ($stored === '' || $this->hasher->isHashed($stored)) {
                continue;
            }

            $preserveNote->execute([$stored, (int) $row['id_employe']]);
            $update->execute([$this->hasher->hash($stored), (int) $row['id_employe']]);
        }
    }
}
