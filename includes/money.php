<?php

/**
 * Affichage et conversion vers franc congolais (CDF / FC).
 */

function money_config(): array
{
    static $cfg = null;
    if ($cfg !== null) {
        return $cfg;
    }

    $app = is_file(dirname(__DIR__) . '/config/app.php')
        ? require dirname(__DIR__) . '/config/app.php'
        : [];

    $cfg = [
        'code' => $app['currency_code'] ?? 'CDF',
        'symbol' => $app['currency_symbol'] ?? 'FC',
        'multiplier' => (float) ($app['eur_to_cdf'] ?? 2800),
        'decimals' => (int) ($app['currency_decimals'] ?? 0),
    ];

    return $cfg;
}

/** Convertit une valeur « unité menu » (ancienne base euro) vers CDF. */
function money_from_menu_unit(float $unit): float
{
    $c = money_config();

    return round($unit * $c['multiplier'], $c['decimals']);
}

/** Formate un montant déjà en CDF. */
function format_money(float $amountCdf): string
{
    $c = money_config();

    return number_format($amountCdf, $c['decimals'], ',', ' ') . ' ' . $c['symbol'];
}

/** @return array{code:string,symbol:string,multiplier:float,decimals:int} */
function money_js_config(): array
{
    $c = money_config();

    return [
        'code' => $c['code'],
        'symbol' => $c['symbol'],
        'multiplier' => $c['multiplier'],
        'decimals' => $c['decimals'],
    ];
}

function contient_ensure_schema(PDO $pdo): void
{
    $cols = array_column($pdo->query('SHOW COLUMNS FROM contient')->fetchAll(PDO::FETCH_ASSOC), 'Field');

    if (!in_array('sauces', $cols, true)) {
        $pdo->exec("ALTER TABLE contient ADD COLUMN sauces VARCHAR(255) NOT NULL DEFAULT '' AFTER sous_total");
        $cols[] = 'sauces';
    }
    if (!in_array('personnalisation_boisson', $cols, true)) {
        $after = in_array('sauces', $cols, true) ? 'sauces' : 'sous_total';
        $pdo->exec("ALTER TABLE contient ADD COLUMN personnalisation_boisson VARCHAR(255) NOT NULL DEFAULT '' AFTER {$after}");
    }
}
