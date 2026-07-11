<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\Application;
use App\Core\Config;

final class TableCodeService
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

    public static function generateTableCode(int $numTable): string
    {
        return 'TBL-' . str_pad((string) $numTable, 3, '0', STR_PAD_LEFT)
            . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
    }

    public function tableEntryUrl(string $codeTable): string
    {
        return $this->config->baseUrl() . '/client/index.php?t=' . rawurlencode($codeTable);
    }
}
