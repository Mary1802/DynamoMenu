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
            && $profile['prenom'] !== '';
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

        $returnTo = $this->peekReturnAfterIdentification();
        $target = $returnTo === 'confirmation.php'
            ? $this->tables->link('confirmation.php')
            : $this->tables->link('index.php');

        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Location: ' . $target, true, $_SERVER['REQUEST_METHOD'] === 'POST' ? 303 : 302);
        exit;
    }

    /** @return array{nom:string,prenom:string,email:string,telephone:string,fidele:bool,id_client:?int}|null */
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
            'fidele' => !empty($_SESSION['client_fidele']),
            'id_client' => isset($_SESSION['id_client']) ? (int) $_SESSION['id_client'] : null,
        ];
    }

    /**
     * @return array{success:true}|array{success:false,error:string}
     */
    public function save(string $nom, string $prenom, string $email, string $telephone, bool $fidele = false): array
    {
        if ($this->isLocked()) {
            return ['success' => false, 'error' => 'Vos informations sont déjà enregistrées et ne peuvent plus être modifiées.'];
        }

        $nom = trim($nom);
        $prenom = trim($prenom);
        $email = trim($email);
        $telephone = trim($telephone);

        if ($nom === '' || $prenom === '') {
            return ['success' => false, 'error' => 'Veuillez renseigner votre nom et votre prénom.'];
        }

        if (!$fidele) {
            $email = '';
            $telephone = '';
        } else {
            if ($telephone === '') {
                return ['success' => false, 'error' => 'Le numéro de téléphone est obligatoire pour devenir client fidèle.'];
            }

            $phoneCheck = self::validateTelephone($telephone);
            if ($phoneCheck !== null) {
                return ['success' => false, 'error' => $phoneCheck];
            }
            $telephone = self::normalizeTelephone($telephone);

            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'error' => 'Adresse e-mail invalide.'];
            }
        }

        $this->clients->ensureSchema();
        try {
            if ($fidele) {
                $idClient = $this->clients->upsert($nom, $prenom, $email, $telephone);
            } else {
                $idClient = $this->clients->createGuest($nom, $prenom);
            }
        } catch (\RuntimeException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }

        $this->session->start();
        $_SESSION['client_nom'] = $nom;
        $_SESSION['client_prenom'] = $prenom;
        $_SESSION['client_email'] = $email !== '' ? mb_strtolower($email, 'UTF-8') : '';
        $_SESSION['client_telephone'] = $telephone;
        $_SESSION['client_fidele'] = $fidele;
        $_SESSION['id_client'] = $idClient;
        $_SESSION['client_identite_locked'] = true;

        return ['success' => true];
    }

    public function requireBeforeOrderValidation(): void
    {
        if ($this->isComplete()) {
            return;
        }

        $this->setReturnAfterIdentification('confirmation.php');
        header('Location: identite.php', true, 302);
        exit;
    }

    public function setReturnAfterIdentification(string $path): void
    {
        $this->session->start();
        $_SESSION['identite_return_to'] = $path;
    }

    public function peekReturnAfterIdentification(): string
    {
        $this->session->start();
        $url = trim((string) ($_SESSION['identite_return_to'] ?? ''));

        return $url !== '' ? $url : 'index.php';
    }

    public function consumeReturnAfterIdentification(): string
    {
        $this->session->start();
        $url = trim((string) ($_SESSION['identite_return_to'] ?? ''));
        unset($_SESSION['identite_return_to']);

        return $url !== '' ? $url : 'index.php';
    }

    public function clear(): void
    {
        $this->session->start();
        unset(
            $_SESSION['client_nom'],
            $_SESSION['client_prenom'],
            $_SESSION['client_email'],
            $_SESSION['client_telephone'],
            $_SESSION['client_fidele'],
            $_SESSION['id_client'],
            $_SESSION['client_identite_locked'],
            $_SESSION['identite_return_to'],
        );
    }

    /**
     * Efface la session et supprime le client en base (après annulation de commande).
     */
    public function eraseFromDatabase(): void
    {
        $this->session->start();
        $idClient = isset($_SESSION['id_client']) ? (int) $_SESSION['id_client'] : 0;

        if ($idClient <= 0) {
            $email = trim((string) ($_SESSION['client_email'] ?? ''));
            $telephone = trim((string) ($_SESSION['client_telephone'] ?? ''));
            if ($email !== '' || $telephone !== '') {
                $idClient = (int) ($this->clients->findIdByEmailAndTelephone($email, $telephone) ?? 0);
            }
        }

        if ($idClient > 0) {
            $this->clients->delete($idClient);
        }

        $this->clear();
    }

    public static function normalizeTelephone(string $telephone): string
    {
        $telephone = trim($telephone);
        $telephone = preg_replace('/[\s\-\.\(\)]/', '', $telephone) ?? '';

        return $telephone;
    }

    public static function validateTelephone(string $telephone): ?string
    {
        $telephone = self::normalizeTelephone($telephone);
        if ($telephone === '') {
            return 'Veuillez renseigner un numéro de téléphone.';
        }

        if (!preg_match('/^\+?\d+$/', $telephone)) {
            return 'Le numéro ne doit contenir que des chiffres, avec éventuellement un + pour l\'indicatif pays (ex. +243).';
        }

        if (str_contains($telephone, '+') && $telephone[0] !== '+') {
            return 'Le signe + ne peut figurer qu\'au début du numéro (indicatif pays).';
        }

        $length = strlen($telephone);
        if ($length < 10 || $length > 13) {
            return 'Le numéro de téléphone doit contenir entre 10 et 13 caractères.';
        }

        if (str_starts_with($telephone, '0') && $length > 10) {
            return 'Un numéro commençant par 0 doit comporter au maximum 10 caractères (ex. 0812345678).';
        }

        if (str_starts_with($telephone, '+') && $length > 13) {
            return 'Un numéro commençant par + doit comporter au maximum 13 caractères (ex. +243812345678).';
        }

        return null;
    }
}
