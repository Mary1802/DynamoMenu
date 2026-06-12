<?php

declare(strict_types=1);

namespace App\Controller\Caissier;

use App\Service\ReportService;

final class ReportController
{
    private ReportService $reports;

    public function __construct(?ReportService $reports = null)
    {
        $this->reports = $reports ?? ReportService::fromApp();
    }

    /** @param array<string, mixed> $get @return array<string, mixed> */
    public function index(array $get): array
    {
        return $this->reports->buildIndexData($get, false);
    }

    /** @param array<string, mixed> $get */
    public function export(array $get, bool $inline): never
    {
        $this->reports->sendPdf($get, 'Caisse', $inline);
    }
}
