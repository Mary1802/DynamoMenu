<?php

declare(strict_types=1);

namespace App\Service;

use App\Auth\ClientSessionService;
use App\Core\Application;
use App\Repository\ClientRepository;

final class ClientProfileService
{
    public function __construct(
        private readonly ClientSessionService $session,
        private readonly TableContextService $tables,
        private readonly ClientRepository $clients,
    ) {
    }

    public static function fromApp(?Application $app = null): self
    {
        $app ??= Application::getInstance();

        return new self(
            $app->clientSession(),
            $app->tableContextService(),
            $app->clientRepository()
        );
    }

    public function isComplete(): bool
    {
        $profile = $this->get();

        return $profile !== null
            && $profile['nom'] !== ''
            && $profile['prenom'] !== ''
            && $profile['email'] !== ''
            && $profile['telephone'] !== '';
    }

    /** Profil enregistré une fois : plus de modification possible. */
    public function isLocked(): bool
    {
        $this->session->start();

        return $this->isComplete() || !empty($_SESSION['client_identite_locked']);
    }

    public function rejectIdentificationPageAccess(): void
    {
        if (!$this->isLocked()) {
            return;
        }

        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Location: index.php', true, $_SERVER['REQUEST_METHOD'] === 'POST' ? 303 : 302);
        exit;
    }

    /** @return array{nom:string,prenom:string,email:string,telephone:string,id_client:?int}|null */
    public function get(): ?array
    {
        $this->session->start();

        $nom = trim((string) ($_SESSION['client_nom'] ?? ''));
        $prenom = trim((string) ($_SESSION['client_prenom'] ?? ''));
        $email = trim((string) ($_SESSION['client_email'] ?? ''));
        $telephone = trim((string) ($_SESSION['client_telephone'] ?? ''));

        if ($nom === '' && $prenom === '' && $email === '' && $telephone === '') {
            return null;
        }

        return [
            'nom' => $nom,
            'prenom' => $prenom,
            'email' => $email,
            'telephone' => $telephone,
            'id_client' => isset($_SESSION['id_client']) ? (int) $_SESSION['id_client'] : null,
        ];
    }

    /**
     * @return array{success:true}|array{success:false,error:string}
     */
    public function save(string $nom, string $prenom, string $email, string $telephone): array
    {
        if ($this->isLocked()) {
            return ['success' => false, 'error' => 'Vos informations sont déjà enregistrées et ne peuvent plus être modifiées.'];
        }

        $nom = trim($nom);
        $prenom = trim($prenom);
        $email = trim($email);
        $telephone = trim($telephone);

        if ($nom === '' || $prenom === '' || $email === '' || $telephone === '') {
            return ['success' => false, 'error' => 'Veuillez remplir tous les champs obligatoires.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Adresse e-mail invalide.'];
        }

        $this->clients->ensureSchema();
        $idClient = $this->clients->upsert($nom, $prenom, $email, $telephone);

        $this->session->start();
        $_SESSION['client_nom'] = $nom;
        $_SESSION['client_prenom'] = $prenom;
        $_SESSION['client_email'] = $email;
        $_SESSION['client_telephone'] = $telephone;
        $_SESSION['id_client'] = $idClient;
        $_SESSION['client_identite_locked'] = true;

        return ['success' => true];
    }

    public function requireWhenTableBound(): void
    {
        if ($this->tables->session() === null || $this->isComplete()) {
            return;
        }

        $script = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        if ($script === 'identite.php') {
            return;
        }

        header('Location: identite.php', true, 302);
        exit;
    }
}
