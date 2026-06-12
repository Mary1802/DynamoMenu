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

    /** @return array{code:string,symbol:string,multiplier:float,decimals:int} */
    public function config(): array
    {
        return [
            'code' => (string) $this->config->get('currency_code', 'CDF'),
            'symbol' => (string) $this->config->get('currency_symbol', 'FC'),
            'multiplier' => (float) $this->config->get('eur_to_cdf', 2800),
            'decimals' => (int) $this->config->get('currency_decimals', 0),
        ];
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

    /** @return array{code:string,symbol:string,multiplier:float,decimals:int} */
    public function jsConfig(): array
    {
        return $this->config();
    }
}
