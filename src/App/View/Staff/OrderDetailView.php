<?php

declare(strict_types=1);

namespace App\View\Staff;

use App\View\View;

final class OrderDetailView
{
    /** @param list<array<string, mixed>> $lignes */
    public static function kitchenLines(array $lignes): void
    {
        View::render('staff/kitchen-order-details', ['lignes' => $lignes]);
    }

    public static function kitchenInstructions(?string $instructions): void
    {
        $instructions = trim((string) ($instructions ?? ''));
        if ($instructions === '') {
            return;
        }
        View::render('staff/kitchen-instructions', ['instructions' => $instructions]);
    }

    /** @param array<string, mixed> $commande @param array<string, string> $statutLabels */
    public static function cuisineFullDetail(array $commande, array $statutLabels): void
    {
        View::render('staff/cuisine-order-detail', [
            'commande' => $commande,
            'statut' => $statutLabels[$commande['statut'] ?? ''] ?? ($commande['statut'] ?? ''),
        ]);
    }

    /** @param array<string, mixed> $commande @param array<string, string> $statutLabels */
    public static function caissierFullDetail(array $commande, array $statutLabels): void
    {
        View::render('staff/caissier-order-detail', [
            'commande' => $commande,
            'statut' => $statutLabels[$commande['statut'] ?? ''] ?? ($commande['statut'] ?? ''),
        ]);
    }
}
