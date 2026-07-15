<?php

declare(strict_types=1);

namespace App\Support;

use App\Core\Application;

/** Facade montants pour templates et scripts (remplace includes/money.php). */
final class Money
{
    /** @return array<string, mixed> */
    public static function config(): array
    {
        return Application::getInstance()->moneyFormatter()->config();
    }

    public static function fromMenuUnit(float $unit): float
    {
        return Application::getInstance()->moneyFormatter()->fromMenuUnit($unit);
    }

    public static function format(float $amountCdf): string
    {
        return Application::getInstance()->moneyFormatter()->format($amountCdf);
    }

    /** Montant à encaisser en CDF (arrondi aux coupures 50 / 100 FC). */
    public static function roundPayable(float $amountCdf): float
    {
        return Application::getInstance()->moneyFormatter()->roundPayable($amountCdf);
    }

    /** @return array<string, mixed> */
    public static function jsConfig(): array
    {
        return Application::getInstance()->moneyFormatter()->jsConfig();
    }
}
