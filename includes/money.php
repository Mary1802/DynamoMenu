<?php

/** Pont procédural → App\Support\MoneyFormatter et schéma contient. */
require_once __DIR__ . '/bootstrap.php';

function money_config(): array
{
    return app()->moneyFormatter()->config();
}

function money_from_menu_unit(float $unit): float
{
    return app()->moneyFormatter()->fromMenuUnit($unit);
}

function format_money(float $amountCdf): string
{
    return app()->moneyFormatter()->format($amountCdf);
}

function money_js_config(): array
{
    return app()->moneyFormatter()->jsConfig();
}

function contient_ensure_schema(PDO $pdo): void
{
    app()->schemaUpgrade()->run();
}
