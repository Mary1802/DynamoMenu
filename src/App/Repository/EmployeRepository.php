<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\Employe;
use PDO;
use PDOException;

final class EmployeRepository extends BaseRepository
{
    public function findByEmailAndRole(string $email, string $role): ?Employe
    {
        $stmt = $this->pdo->prepare(
            'SELECT id_employe, nom_employe, prenom_employe, email_employe, mot_de_passe, role, telephone_employe
             FROM employe WHERE email_employe = ? AND role = ? LIMIT 1'
        );
        $stmt->execute([trim($email), $role]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? Employe::fromRow($row) : null;
    }

    public function findByEmail(string $email): ?Employe
    {
        $stmt = $this->pdo->prepare(
            'SELECT id_employe, nom_employe, prenom_employe, email_employe, mot_de_passe, role, telephone_employe
             FROM employe WHERE email_employe = ? LIMIT 1'
        );
        $stmt->execute([trim($email)]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? Employe::fromRow($row) : null;
    }

    public function findById(int $id): ?Employe
    {
        $stmt = $this->pdo->prepare('SELECT * FROM employe WHERE id_employe = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? Employe::fromRow($row) : null;
    }

    /** @return list<Employe> */
    public function search(?string $query = null): array
    {
        if ($query !== null && $query !== '') {
            $pattern = '%' . $query . '%';
            $stmt = $this->pdo->prepare(
                'SELECT * FROM employe WHERE nom_employe LIKE ? OR prenom_employe LIKE ? OR email_employe LIKE ?
                 ORDER BY role, nom_employe'
            );
            $stmt->execute([$pattern, $pattern, $pattern]);
        } else {
            $stmt = $this->pdo->query('SELECT * FROM employe ORDER BY role, nom_employe');
        }

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(static fn(array $row): Employe => Employe::fromRow($row), $rows);
    }

    public function create(
        string $nom,
        string $prenom,
        string $email,
        string $passwordHash,
        string $role,
        string $telephone = '',
        ?string $passwordNote = null
    ): void {
        $stmt = $this->pdo->prepare(
            'INSERT INTO employe (nom_employe, prenom_employe, email_employe, mot_de_passe, mot_de_passe_note, role, telephone_employe)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$nom, $prenom, $email, $passwordHash, $passwordNote, $role, $telephone]);
    }

    public function updatePassword(int $id, string $passwordHash, ?string $passwordNote = null): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE employe SET mot_de_passe = ?, mot_de_passe_note = ? WHERE id_employe = ?'
        );
        $stmt->execute([$passwordHash, $passwordNote, $id]);
    }

    public function updateRole(int $id, string $role): void
    {
        $stmt = $this->pdo->prepare('UPDATE employe SET role = ? WHERE id_employe = ?');
        $stmt->execute([$role, $id]);
    }

    public function updateTelephone(int $id, string $telephone): void
    {
        $stmt = $this->pdo->prepare('UPDATE employe SET telephone_employe = ? WHERE id_employe = ?');
        $stmt->execute([$telephone, $id]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM employe WHERE id_employe = ?');
        $stmt->execute([$id]);
    }

    public function count(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM employe')->fetchColumn();
    }
}
