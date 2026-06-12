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

        $label = $this->nomBoisson ?? 'Article';
        if ($this->personnalisationBoisson !== null && $this->personnalisationBoisson !== '') {
            $label .= ' — ' . $this->personnalisationBoisson;
        }

        return $label;
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            (int) ($row['quantite'] ?? 0),
            (float) ($row['prix'] ?? 0),
            (float) ($row['sous_total'] ?? 0),
            isset($row['nom_plat']) ? (string) $row['nom_plat'] : null,
            isset($row['nom_boisson']) ? (string) $row['nom_boisson'] : null,
            isset($row['sauces']) ? (string) $row['sauces'] : null,
            isset($row['personnalisation_boisson']) ? (string) $row['personnalisation_boisson'] : null,
        );
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
        ];
    }
}
