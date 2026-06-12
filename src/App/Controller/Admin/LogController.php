<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Core\Application;

final class LogController
{
    private Application $app;

    public function __construct(?Application $app = null)
    {
        $this->app = $app ?? Application::getInstance();
    }

    /**
     * @param array<string, mixed> $get
     * @return array{logs:list<array<string,mixed>>,q:string}
     */
    public function handle(array $get): array
    {
        $q = trim((string) ($get['q'] ?? ''));

        return [
            'logs' => $this->app->activityLog()->findRecent($q !== '' ? $q : null),
            'q' => $q,
        ];
    }
}
