<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Core\Application;
use App\Service\ReportService;

final class ReportController
{
    private ReportService $reports;

    public function __construct(?Application $app = null)
    {
        $this->reports = ReportService::fromApp($app);
    }

    /** @param array<string, mixed> $get @return array<string, mixed> */
    public function index(array $get): array
    {
        return $this->reports->buildIndexData($get, true);
    }

    /** @param array<string, mixed> $get */
    public function export(array $get, bool $inline): never
    {
        $this->reports->sendPdf($get, 'Administration', $inline);
    }
}
