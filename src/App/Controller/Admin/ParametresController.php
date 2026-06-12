<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Core\Application;
use App\Service\StaffSettingsService;

final class ParametresController
{
    private Application $app;
    private StaffSettingsService $settings;

    public function __construct(?Application $app = null)
    {
        $this->app = $app ?? Application::getInstance();
        $this->settings = StaffSettingsService::fromApp($this->app);
    }

    /**
     * @param array<string, mixed> $get
     * @param array<string, mixed> $post
     * @param array<string, mixed> $user
     * @return array{
     *   message:string,
     *   contactList:list<array<string,mixed>>,
     *   currentContact:array<string,mixed>|null,
     *   account:array<string,mixed>,
     *   editingId:int
     * }
     */
    public function handle(array $get, array $post, array $user): array
    {
        $this->settings->ensureSchema();
        $contacts = $this->app->contactRepository();
        $message = '';
        $editingId = isset($get['edit']) ? (int) $get['edit'] : 0;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($post['save_contact'])) {
                $nom = trim((string) ($post['nom'] ?? ''));
                $adresse = trim((string) ($post['adresse'] ?? ''));
                $telephone = trim((string) ($post['telephone'] ?? ''));
                $whatsapp = trim((string) ($post['whatsapp'] ?? ''));
                $email = trim((string) ($post['email'] ?? ''));
                $horaires = trim((string) ($post['horaires'] ?? ''));

                if ($nom === '') {
                    $message = 'Le nom est requis.';
                } elseif (!empty($post['id_contact'])) {
                    $editingId = (int) $post['id_contact'];
                    $contacts->update($editingId, $nom, $adresse, $telephone, $whatsapp, $email, $horaires);
                    $message = 'Contact mis à jour.';
                } else {
                    $contacts->create($nom, $adresse, $telephone, $whatsapp, $email, $horaires);
                    $message = 'Contact ajouté.';
                    $editingId = 0;
                }
            }

            if (isset($post['delete_contact']) && !empty($post['id_contact'])) {
                $contacts->delete((int) $post['id_contact']);
                $message = 'Contact supprimé.';
                $editingId = 0;
            }
        }

        $contactList = $this->settings->contactList();
        $currentContact = $editingId > 0 ? $contacts->findById($editingId) : null;

        return [
            'message' => $message,
            'contactList' => $contactList,
            'currentContact' => $currentContact,
            'account' => $this->settings->staffAccount($user),
            'editingId' => $editingId,
        ];
    }
}
