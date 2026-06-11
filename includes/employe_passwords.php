<?php

/**
 * Migration technique des mots de passe employés (sans réinitialisation de comptes).
 */

require_once __DIR__ . '/session_security.php';

function employe_ensure_password_column(PDO $pdo): void
{
    try {
        $col = $pdo->query("SHOW COLUMNS FROM employe LIKE 'mot_de_passe'")->fetch(PDO::FETCH_ASSOC);
        if (!$col) {
            return;
        }

        if (preg_match('/varchar\((\d+)\)/i', (string) ($col['Type'] ?? ''), $match)) {
            if ((int) $match[1] < 255) {
                $pdo->exec('ALTER TABLE employe MODIFY mot_de_passe VARCHAR(255) NOT NULL');
            }
        }
    } catch (PDOException $e) {
        // Table absente ou droits insuffisants
    }
}

/** Convertit d'anciens mots de passe en clair (créés avant le hachage) en hash bcrypt. */
function employe_upgrade_passwords(PDO $pdo): void
{
    employe_ensure_password_column($pdo);

    try {
        $rows = $pdo->query('SELECT id_employe, mot_de_passe FROM employe')->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return;
    }

    $update = $pdo->prepare('UPDATE employe SET mot_de_passe = ? WHERE id_employe = ?');

    foreach ($rows as $row) {
        $stored = trim((string) ($row['mot_de_passe'] ?? ''));
        if ($stored === '' || password_is_hashed($stored)) {
            continue;
        }

        $update->execute([password_hash_employe($stored), (int) $row['id_employe']]);
    }
}

function employe_password_is_valid(string $password): bool
{
    return strlen($password) >= 6;
}
