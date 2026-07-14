<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Core\Application;
use App\Service\ClientProfileService;
use RuntimeException;

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
     * @return array{message:string,error:string,clients:list<array<string,mixed>>,q:string}
     */
    public function handle(array $get, array $post): array
    {
        $message = '';
        $error = '';
        $repo = $this->app->clientRepository();
        $repo->ensureSchema();
        $log = $this->app->activityLog();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($post['update_client'])) {
                $id = (int) ($post['id_client'] ?? 0);
                $nom = trim((string) ($post['nom_client'] ?? ''));
                $prenom = trim((string) ($post['prenom_client'] ?? ''));
                $email = trim((string) ($post['email_client'] ?? ''));
                $telephone = trim((string) ($post['telephone_client'] ?? ''));

                if ($id <= 0 || $nom === '' || $prenom === '' || $email === '' || $telephone === '') {
                    $error = 'Tous les champs sont obligatoires.';
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error = 'Adresse e-mail invalide.';
                } else {
                    $phoneCheck = ClientProfileService::validateTelephone($telephone);
                    if ($phoneCheck !== null) {
                        $error = $phoneCheck;
                    } else {
                        try {
                            $repo->updateById($id, $nom, $prenom, $email, $telephone);
                            $log->log('client_update', "Client #{$id} modifié", 'admin');
                            $message = 'Client mis à jour.';
                        } catch (RuntimeException $e) {
                            $error = $e->getMessage();
                        }
                    }
                }
            }

            if (isset($post['delete_client'])) {
                $id = (int) ($post['id_client'] ?? 0);
                if ($id <= 0) {
                    $error = 'Client invalide.';
                } else {
                    $repo->delete($id);
                    $log->log('client_delete', "Client #{$id} supprimé", 'admin');
                    $message = 'Client supprimé.';
                }
            }
        }

        $q = trim((string) ($get['q'] ?? ''));
        $clients = $repo->findForAdmin($q !== '' ? $q : null);

        return [
            'message' => $message,
            'error' => $error,
            'clients' => $clients,
            'q' => $q,
        ];
    }
}
