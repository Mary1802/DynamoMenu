<?php

declare(strict_types=1);

namespace App\Support;

use App\Core\Application;
use App\Core\Config;

final class MoneyFormatter
{
    public function __construct(
        private readonly Config $config
    ) {
    }

    public static function fromApp(?Application $app = null): self
    {
        $app ??= Application::getInstance();

        return new self($app->config());
    }

    /** @return array{code:string,symbol:string,multiplier:float,decimals:int,tva_rate:float} */
    public function config(): array
    {
        return [
            'code' => (string) $this->config->get('currency_code', 'CDF'),
            'symbol' => (string) $this->config->get('currency_symbol', 'FC'),
            'multiplier' => (float) $this->config->get('eur_to_cdf', 2300),
            'decimals' => (int) $this->config->get('currency_decimals', 0),
            'tva_rate' => (float) $this->config->get('tva_rate', 0.16),
        ];
    }

    public function tvaRate(): float
    {
        return (float) $this->config->get('tva_rate', 0.16);
    }

    /** Extrait le HT d'un montant TTC. */
    public function htFromTtc(float $ttc): float
    {
        $c = $this->config();
        $rate = $this->tvaRate();

        return round($ttc / (1 + $rate), $c['decimals']);
    }

    /** Part TVA d'un montant TTC. */
    public function tvaFromTtc(float $ttc): float
    {
        return round($ttc - $this->htFromTtc($ttc), $this->config()['decimals']);
    }

    public function fromMenuUnit(float $unit): float
    {
        $c = $this->config();

        return round($unit * $c['multiplier'], $c['decimals']);
    }

    public function format(float $amountCdf): string
    {
        $c = $this->config();

        return number_format($amountCdf, $c['decimals'], ',', ' ') . ' ' . $c['symbol'];
    }

    /**
     * Arrondit un montant CDF au multiple de 50 FC supérieur
     * (coupure minimale 50 FC → montants finissant par 00 ou 50).
     * Ex. : 12 320 → 12 350 ; 12 351 → 12 400 ; 12 350 → 12 350.
     */
    public function roundPayable(float $amountCdf): float
    {
        $decimals = $this->config()['decimals'];
        $amount = round(max(0, $amountCdf), $decimals);
        if ($amount <= 0) {
            return 0.0;
        }

        $step = 50.0;
        $rounded = ceil($amount / $step) * $step;

        return round($rounded, $decimals);
    }

    /** @return array{code:string,symbol:string,multiplier:float,decimals:int,tva_rate:float} */
    public function jsConfig(): array
    {
        return $this->config();
    }
}
