<?php

declare(strict_types=1);

namespace App\Model;

final class CommandeLine
{
    public function __construct(
        public readonly int $quantite,
        public readonly float $prix,
        public readonly float $sousTotal,
        public readonly ?string $nomPlat = null,
        public readonly ?string $nomBoisson = null,
        public readonly ?string $sauces = null,
        public readonly ?string $personnalisationBoisson = null,
        public readonly ?int $idPlat = null,
        public readonly ?int $idBoisson = null,
    ) {
    }

    public function label(): string
    {
        if ($this->nomPlat !== null && $this->nomPlat !== '') {
            $label = $this->nomPlat;
            if ($this->sauces !== null && $this->sauces !== '') {
                $label .= ' — Sauces: ' . $this->sauces;
            }

            return $label;
        }

        if ($this->nomBoisson !== null && $this->nomBoisson !== '') {
            $label = $this->nomBoisson;
            if ($this->personnalisationBoisson !== null && $this->personnalisationBoisson !== '') {
                $label .= ' — ' . $this->personnalisationBoisson;
            }

            return $label;
        }

        $fallback = trim((string) ($this->personnalisationBoisson ?? ''));

        return $fallback !== '' ? $fallback : 'Article';
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        $idPlat = isset($row['id_plat']) && $row['id_plat'] !== null && $row['id_plat'] !== ''
            ? (int) $row['id_plat']
            : 0;
        $idBoisson = isset($row['id_boisson']) && $row['id_boisson'] !== null && $row['id_boisson'] !== ''
            ? (int) $row['id_boisson']
            : 0;

        $nomPlat = self::nullableString($row['nom_plat'] ?? null);
        $nomBoisson = self::nullableString($row['nom_boisson'] ?? null);

        // Une ligne = un type : éviter d'afficher un plat et une boisson croisés.
        if ($idPlat > 0) {
            $idBoisson = 0;
            $nomBoisson = null;
        } elseif ($idBoisson > 0) {
            $idPlat = 0;
            $nomPlat = null;
        }

        return new self(
            (int) ($row['quantite'] ?? 0),
            (float) ($row['prix'] ?? 0),
            (float) ($row['sous_total'] ?? 0),
            $nomPlat,
            $nomBoisson,
            self::nullableString($row['sauces'] ?? null),
            self::nullableString($row['personnalisation_boisson'] ?? null),
            $idPlat > 0 ? $idPlat : null,
            $idBoisson > 0 ? $idBoisson : null,
        );
    }

    private static function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $s = trim((string) $value);

        return $s !== '' ? $s : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'quantite' => $this->quantite,
            'prix' => $this->prix,
            'sous_total' => $this->sousTotal,
            'nom_plat' => $this->nomPlat,
            'nom_boisson' => $this->nomBoisson,
            'sauces' => $this->sauces,
            'personnalisation_boisson' => $this->personnalisationBoisson,
            'id_plat' => $this->idPlat,
            'id_boisson' => $this->idBoisson,
        ];
    }
}
