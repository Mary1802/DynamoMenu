<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Core\Application;

final class ClientController
{
    private Application $app;

    public function __construct(?Application $app = null)
    {
        $this->app = $app ?? Application::getInstance();
    }

    /**
     * @param array<string, mixed> $get
     * @return array{message:string,clients:list<array<string,mixed>>,q:string}
     */
    public function handle(array $get, array $post): array
    {
        $repo = $this->app->clientRepository();
        $q = trim((string) ($get['q'] ?? ''));
        $clients = $repo->findForAdmin($q !== '' ? $q : null);

        return [
            'message' => '',
            'clients' => $clients,
            'q' => $q,
        ];
    }
}
