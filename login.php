<?php
session_start();

// Configuration de la base de données
$db_config = require 'config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';

    if (empty($email) || empty($password) || empty($role)) {
        $error = 'Veuillez remplir tous les champs';
    } else {
        try {
            $pdo = new PDO(
                "mysql:host=" . $db_config['host'] . ";dbname=" . $db_config['dbname'],
                $db_config['user'],
                $db_config['password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            $stmt = $pdo->prepare("SELECT id_employe, nom_employe, prenom_employe, email_employe, mot_de_passe FROM employe WHERE email_employe = ? AND role = ?");
            $stmt->execute([$email, $role]);
            $employe = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($employe && $employe['mot_de_passe'] === $password) {
                $_SESSION['user_id'] = $employe['id_employe'];
                $_SESSION['nom'] = $employe['nom_employe'] . ' ' . $employe['prenom_employe'];
                $_SESSION['email'] = $employe['email_employe'];
                $_SESSION['role'] = $role;

                if ($role === 'cuisinier') {
                    header('Location: cuisine/dashboard.php');
                } elseif ($role === 'caissier') {
                    header('Location: caissier/paiement.php');
                } elseif ($role === 'admin') {
                    header('Location: admin/dashboard.php');
                } else {
                    header('Location: client/index.php');
                }
                exit;
            } else {
                $error = 'Email, mot de passe ou rôle incorrect.';
            }
        } catch (PDOException $e) {
            $error = 'Erreur de connexion : ' . htmlspecialchars($e->getMessage()) . '<br><a href="init_db.php" style="color: #ff6f1f; text-decoration: underline;">Initialiser la base de données</a>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - DynamoMenu</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            background: linear-gradient(180deg, #070707, #0b0b0d);
            color: #e6e6e6;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-container {
            background: linear-gradient(180deg, #0f0f10, #0e0e10);
            border: 1px solid rgba(255, 255, 255, 0.03);
            border-radius: 12px;
            padding: 40px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5);
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header h1 {
            font-size: 1.8rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 8px;
        }

        .login-header p {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.9rem;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            color: rgba(255, 255, 255, 0.8);
            font-weight: 500;
            margin-bottom: 8px;
        }

        .form-control {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #e6e6e6;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 0.95rem;
        }

        .form-control:focus {
            background: rgba(0, 0, 0, 0.4);
            border-color: rgba(255, 111, 31, 0.3);
            color: #e6e6e6;
            box-shadow: 0 0 0 0.2rem rgba(255, 111, 31, 0.15);
        }

        .form-select {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #e6e6e6;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 0.95rem;
        }

        .form-select:focus {
            background: rgba(0, 0, 0, 0.4);
            border-color: rgba(255, 111, 31, 0.3);
            box-shadow: 0 0 0 0.2rem rgba(255, 111, 31, 0.15);
        }

        .form-select option {
            background: #0f0f10;
            color: #e6e6e6;
        }

        .btn-login {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #ff6f1f, #ff8a3d);
            border: 0;
            color: #fff;
            font-weight: 600;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            background: linear-gradient(135deg, #e0601a, #ff7a2d);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 111, 31, 0.3);
            color: #fff;
        }

        .alert {
            background: rgba(220, 53, 69, 0.1);
            border: 1px solid rgba(220, 53, 69, 0.3);
            color: #ff6b6b;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: rgba(255, 111, 31, 0.8);
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }

        .back-link a:hover {
            color: #ff6f1f;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>🍳 DynamoMenu</h1>
            <p>Connexion Employé</p>
        </div>

        <?php if ($error): ?>
            <div class="alert">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="email" class="form-label">Email</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="votre@email.com" required>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Mot de passe</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>

            <div class="form-group">
                <label for="role" class="form-label">Rôle</label>
                <select id="role" name="role" class="form-select" required>
                    <option value="">-- Sélectionner un rôle --</option>
                    <option value="cuisinier">Cuisinier</option>
                    <option value="caissier">Caissier</option>
                    <option value="admin">Administrateur</option>
                </select>
            </div>

            <button type="submit" class="btn-login">Se connecter</button>
        </form>

        <div class="back-link">
            <a href="client/index.php">← Retour à l'accueil</a>
        </div>
    </div>

    <script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
