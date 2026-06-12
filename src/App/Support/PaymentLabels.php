<?php

declare(strict_types=1);

namespace App\Support;

final class PaymentLabels
{
    public static function mode(string $mode): string
    {
        return match ($mode) {
            'especes' => 'Espèces',
            'mobile' => 'Mobile money',
            'carte' => 'Carte',
            default => $mode,
        };
    }

    public static function dashboardMode(string $mode): string
    {
        return match ($mode) {
            'especes' => 'Cash / Espèces',
            'mobile' => 'Mobile money',
            'carte' => 'Carte bancaire',
            default => ucfirst($mode),
        };
    }
}
