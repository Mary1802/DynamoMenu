<?php

declare(strict_types=1);

namespace App\Controller\Staff;

use App\Core\Application;
use App\Controller\Admin\EmployeController;
use PDOException;

final class LoginController
{
    private Application $app;

    public function __construct(?Application $app = null)
    {
        $this->app = $app ?? Application::getInstance();
    }

    /**
     * @return array{error:string,success:string,postEmail:string}
     */
    public function handle(): array
    {
        $auth = $this->app->staffAuth();
        $auth->startSession();
        $this->app->schemaUpgrade()->run();
        $this->app->employePasswordService()->upgradePlaintextPasswords();

        $error = '';
        $success = isset($_GET['logout']) ? 'Vous êtes déconnecté.' : '';

        if (isset($_GET['err'])) {
            $error = match ($_GET['err']) {
                'role' => 'Accès refusé pour ce rôle.',
                'session' => 'Session expirée ou compte modifié. Reconnectez-vous.',
                'db' => 'Impossible de vérifier la session.',
                default => 'Veuillez vous connecter.',
            };
        }

        $current = $auth->user();
        if ($current !== null) {
            header('Location: ' . $auth->dashboardUrl($current['role']));
            exit;
        }

        $postEmail = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->app->csrf()->verify()) {
                $error = 'Session expirée. Rechargez la page et réessayez.';
            } else {
                $email = trim((string) ($_POST['email'] ?? ''));
                $password = (string) ($_POST['password'] ?? '');
                $postEmail = $email;

                if ($email === '' || $password === '') {
                    $error = 'Veuillez renseigner votre e-mail et votre mot de passe.';
                } else {
                    try {
                        $employe = $this->app->employeRepository()->findByEmail($email);

                        if ($employe === null) {
                            $error = 'E-mail ou mot de passe incorrect.';
                        } elseif (!isset(EmployeController::ROLES[$employe->role])) {
                            $error = 'Ce compte existe mais son rôle n\'est pas valide. Un administrateur doit le corriger dans Admin → Employés.';
                        } elseif (
                            !$this->app->passwordHasher()->verify($password, (string) $employe->motDePasse)
                        ) {
                            $error = 'E-mail ou mot de passe incorrect.';
                        } else {
                            if ($this->app->passwordHasher()->needsRehash((string) $employe->motDePasse)) {
                                $this->app->employeRepository()->updatePassword(
                                    $employe->id,
                                    $this->app->passwordHasher()->hash($password)
                                );
                            }
                            $role = $employe->role;
                            $auth->login($employe->toLoginArray(), $role);
                            header('Location: ' . $auth->dashboardUrl($role));
                            exit;
                        }
                    } catch (PDOException $e) {
                        $error = 'Erreur de connexion. Vérifiez la configuration de la base de données.';
                    }
                }
            }
        }

        return [
            'error' => $error,
            'success' => $success,
            'postEmail' => $postEmail,
        ];
    }
}
