<?php

declare(strict_types=1);

namespace App\Controller\Staff;

use App\Core\Application;
use PDOException;

final class LoginController
{
    private Application $app;

    public function __construct(?Application $app = null)
    {
        $this->app = $app ?? Application::getInstance();
    }

    /**
     * @return array{error:string,success:string,postRole:string}
     */
    public function handle(): array
    {
        $auth = $this->app->staffAuth();
        $auth->startSession();
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

        $postRole = (string) ($_POST['role'] ?? '');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->app->csrf()->verify()) {
                $error = 'Session expirée. Rechargez la page et réessayez.';
            } else {
                $email = trim($_POST['email'] ?? '');
                $password = (string) ($_POST['password'] ?? '');
                $role = (string) ($_POST['role'] ?? '');

                if ($email === '' || $password === '' || $role === '') {
                    $error = 'Veuillez remplir tous les champs.';
                } else {
                    try {
                        $employe = $this->app->employeRepository()->findByEmailAndRole($email, $role);

                        if (
                            $employe !== null
                            && $this->app->passwordHasher()->verify($password, (string) $employe->motDePasse)
                            && $employe->role === $role
                        ) {
                            if ($this->app->passwordHasher()->needsRehash((string) $employe->motDePasse)) {
                                $this->app->employeRepository()->updatePassword(
                                    $employe->id,
                                    $this->app->passwordHasher()->hash($password)
                                );
                            }
                            $auth->login($employe->toLoginArray(), $role);
                            header('Location: ' . $auth->dashboardUrl($role));
                            exit;
                        }

                        $error = 'Email, mot de passe ou rôle incorrect.';
                    } catch (PDOException $e) {
                        $error = 'Erreur de connexion. Vérifiez la configuration de la base de données.';
                    }
                }
            }
        }

        return [
            'error' => $error,
            'success' => $success,
            'postRole' => $postRole,
        ];
    }
}
