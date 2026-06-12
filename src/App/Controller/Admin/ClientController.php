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
     * @param array<string, mixed> $post
     * @return array{message:string,clients:list<array<string,mixed>>,q:string}
     */
    public function handle(array $get, array $post): array
    {
        $message = '';
        $fidelity = $this->app->fidelityService();
        $fidelity->ensureSchema();
        $repo = $this->app->clientRepository();
        $log = $this->app->activityLog();

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($post['adjust_points'])) {
            $id = (int) ($post['id_client'] ?? 0);
            $delta = (int) ($post['delta'] ?? 0);
            $note = trim((string) ($post['note'] ?? 'Ajustement admin'));

            if ($id > 0 && $delta !== 0) {
                $fidelity->adjustPoints($id, $delta, $note);
                $log->log('fidelite_ajust', "Client #{$id} : {$delta} pts — {$note}", 'fidelite');
                $message = 'Points mis à jour.';
            }
        }

        $q = trim((string) ($get['q'] ?? ''));
        $clients = $repo->findWithFidelity($q !== '' ? $q : null);

        foreach ($clients as &$client) {
            $client['niveau_label'] = $fidelity->niveauLabelFor(
                isset($client['niveau_fidelite']) ? (string) $client['niveau_fidelite'] : null,
                (int) $client['points']
            );
        }
        unset($client);

        return [
            'message' => $message,
            'clients' => $clients,
            'q' => $q,
        ];
    }
}
