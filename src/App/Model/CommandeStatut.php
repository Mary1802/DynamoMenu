<?php

declare(strict_types=1);

namespace App\Model;

final class CommandeStatut
{
    public const EN_ATTENTE = 'en_attente';
    public const EN_PREPARATION = 'en_preparation';
    public const PRETE = 'prete';
    public const LIVREE = 'livree';
    public const ANNULEE = 'annulee';

    /** @return array<string, string> */
    public static function labels(): array
    {
        return [
            self::EN_ATTENTE => 'En attente',
            self::EN_PREPARATION => 'En préparation',
            self::PRETE => 'Prête',
            self::LIVREE => 'Livrée',
            self::ANNULEE => 'Annulée',
        ];
    }

    public static function isValid(string $statut): bool
    {
        return isset(self::labels()[$statut]);
    }

    public static function label(string $statut): string
    {
        return self::labels()[$statut] ?? $statut;
    }

    /** @return array<string, string> */
    public static function clientLabels(): array
    {
        return [
            self::EN_ATTENTE => 'En attente en cuisine',
            self::EN_PREPARATION => 'En préparation',
            self::PRETE => 'Prête — en cours de service',
            self::LIVREE => 'Livrée à votre table',
            self::ANNULEE => 'Annulée',
        ];
    }

    public static function clientLabel(string $statut): string
    {
        return self::clientLabels()[$statut] ?? $statut;
    }
}
