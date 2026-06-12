<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Core\Application;
use App\Model\Employe;
use PDOException;

final class EmployeController
{
    /** @var array<string, string> */
    public const ROLES = [
        'admin' => 'Administrateur',
        'cuisinier' => 'Cuisinier',
        'caissier' => 'Caissier',
    ];

    private Application $app;

    public function __construct(?Application $app = null)
    {
        $this->app = $app ?? Application::getInstance();
    }

    /**
     * @param array<string, mixed> $post
     * @param array<string, mixed> $session
     * @return array{message:string,error:string,employes:list<Employe>,q:string,passwordHasher:\App\Security\PasswordHasher,passwordService:\App\Service\EmployePasswordService}
     */
    public function handle(array $get, array $post, array $session): array
    {
        $message = '';
        $error = '';
        $repo = $this->app->employeRepository();
        $passwords = $this->app->employePasswordService();
        $passwords->syncPasswordNotes();
        $hasher = $this->app->passwordHasher();
        $log = $this->app->activityLog();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($post['add_employe'])) {
                $email = trim((string) ($post['email_employe'] ?? ''));
                $mdp = (string) ($post['mot_de_passe'] ?? '');
                $role = (string) ($post['role'] ?? '');

                if ($email === '' || $mdp === '') {
                    $error = 'Email et mot de passe requis.';
                } elseif (!$hasher->isValidLength($mdp)) {
                    $error = 'Le mot de passe doit contenir au moins 6 caractères.';
                } elseif (!isset(self::ROLES[$role])) {
                    $error = 'Rôle invalide.';
                } else {
                    try {
                        $repo->create(
                            trim((string) ($post['nom_employe'] ?? '')),
                            trim((string) ($post['prenom_employe'] ?? '')),
                            $email,
                            $hasher->hash($mdp),
                            $role,
                            trim((string) ($post['telephone_employe'] ?? '')),
                            $mdp
                        );
                        $log->log('employe_create', "Employé créé : {$email} ({$role})");
                        $message = 'Compte créé. L\'employé peut se connecter avec son email et le mot de passe défini.';
                    } catch (PDOException $e) {
                        $error = 'Cet email est déjà utilisé ou la création a échoué.';
                    }
                }
            }

            if (isset($post['reset_password'])) {
                $id = (int) ($post['id_employe'] ?? 0);
                $mdp = (string) ($post['nouveau_mot_de_passe'] ?? '');

                if ($id <= 0 || $mdp === '') {
                    $error = 'Employé et nouveau mot de passe requis.';
                } elseif (!$hasher->isValidLength($mdp)) {
                    $error = 'Le mot de passe doit contenir au moins 6 caractères.';
                } else {
                    $employe = $repo->findById($id);
                    if ($employe === null) {
                        $error = 'Employé introuvable.';
                    } else {
                        $repo->updatePassword($id, $hasher->hash($mdp), $mdp);
                        $log->log('employe_password_reset', 'Mot de passe réinitialisé : ' . $employe->email);
                        $message = 'Mot de passe mis à jour pour ' . $employe->email . '.';
                    }
                }
            }

            if (isset($post['delete_employe'])) {
                $id = (int) ($post['id_employe'] ?? 0);
                if ($id !== (int) ($session['user_id'] ?? 0)) {
                    $repo->delete($id);
                    $log->log('employe_delete', "Employé #{$id} supprimé");
                    $message = 'Employé supprimé.';
                } else {
                    $error = 'Vous ne pouvez pas supprimer votre propre compte.';
                }
            }
        }

        $q = trim((string) ($get['q'] ?? ''));

        return [
            'message' => $message,
            'error' => $error,
            'employes' => $repo->search($q !== '' ? $q : null),
            'q' => $q,
            'passwordHasher' => $hasher,
            'passwordService' => $passwords,
        ];
    }
}
