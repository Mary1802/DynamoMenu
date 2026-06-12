<?php

declare(strict_types=1);

namespace App\Model;

use App\Security\PasswordHasher;

final class Employe
{
    public function __construct(
        public readonly int $id,
        public readonly string $nom,
        public readonly string $prenom,
        public readonly string $email,
        public readonly string $role,
        public readonly ?string $telephone = null,
        public readonly ?string $motDePasse = null,
        public readonly ?string $passwordNote = null,
    ) {
    }

    public function fullName(): string
    {
        return trim($this->prenom . ' ' . $this->nom);
    }

    public function visiblePassword(PasswordHasher $hasher): ?string
    {
        if ($this->passwordNote !== null && trim($this->passwordNote) !== '') {
            return $this->passwordNote;
        }

        $stored = trim((string) ($this->motDePasse ?? ''));
        if ($stored !== '' && !$hasher->isHashed($stored)) {
            return $stored;
        }

        return null;
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            (int) $row['id_employe'],
            (string) ($row['nom_employe'] ?? ''),
            (string) ($row['prenom_employe'] ?? ''),
            (string) ($row['email_employe'] ?? ''),
            (string) ($row['role'] ?? ''),
            isset($row['telephone_employe']) ? (string) $row['telephone_employe'] : null,
            isset($row['mot_de_passe']) ? (string) $row['mot_de_passe'] : null,
            isset($row['mot_de_passe_note']) && trim((string) $row['mot_de_passe_note']) !== ''
                ? (string) $row['mot_de_passe_note']
                : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toLoginArray(): array
    {
        return [
            'id_employe' => $this->id,
            'nom_employe' => $this->nom,
            'prenom_employe' => $this->prenom,
            'email_employe' => $this->email,
            'role' => $this->role,
            'mot_de_passe' => $this->motDePasse ?? '',
        ];
    }
}
