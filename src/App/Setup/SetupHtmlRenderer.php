<?php

declare(strict_types=1);

namespace App\Setup;

/** Rendu HTML des scripts d'installation / migration. */
final class SetupHtmlRenderer
{
    public static function initSuccess(): void
    {
        echo "<div style='background: linear-gradient(180deg, #070707, #0b0b0d); color: #e6e6e6; min-height: 100vh; padding: 40px; font-family: Arial, sans-serif;'>";
        echo "<div style='max-width: 700px; margin: 0 auto; background: rgba(15, 15, 16, 0.8); padding: 30px; border-radius: 12px; border: 1px solid rgba(255, 255, 255, 0.1);'>";
        echo "<h1 style='color: #ff6f1f; margin-bottom: 20px;'>✓ Base de données initialisée</h1>";
        echo "<p style='color: rgba(255, 255, 255, 0.75);'>Schéma créé selon le MCD et données de test ajoutées.</p>";
        echo "<h3 style='color: #fff; margin-top: 20px;'>Identifiants de test :</h3>";
        echo "<div style='background: rgba(0, 0, 0, 0.2); padding: 15px; border-radius: 8px; margin: 10px 0;'>";
        echo "<strong>Cuisinier :</strong><br>";
        echo "Email : <code style='background: rgba(0,0,0,0.4); padding: 2px 6px; border-radius: 3px;'>pierre@dynamomenu.fr</code><br>";
        echo "Mot de passe : <code style='background: rgba(0,0,0,0.4); padding: 2px 6px; border-radius: 3px;'>chef123</code><br><br>";
        echo "<strong>Caissier :</strong><br>";
        echo "Email : <code style='background: rgba(0,0,0,0.4); padding: 2px 6px; border-radius: 3px;'>jean@dynamomenu.fr</code><br>";
        echo "Mot de passe : <code style='background: rgba(0,0,0,0.4); padding: 2px 6px; border-radius: 3px;'>caisse123</code><br><br>";
        echo "<strong>Admin :</strong><br>";
        echo "Email : <code style='background: rgba(0,0,0,0.4); padding: 2px 6px; border-radius: 3px;'>admin@dynamomenu.fr</code><br>";
        echo "Mot de passe : <code style='background: rgba(0,0,0,0.4); padding: 2px 6px; border-radius: 3px;'>admin123</code>";
        echo "</div>";
        echo "<a href='login.php' style='display: inline-block; background: linear-gradient(135deg, #ff6f1f, #ff8a3d); color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; margin-top: 20px; font-weight: bold;'>Aller à la connexion →</a>";
        echo "</div></div>";
    }

    public static function initError(string $message): void
    {
        echo "<div style='background: #dc3545; color: white; padding: 20px; border-radius: 8px;'>";
        echo "<strong>Erreur d'initialisation :</strong><br>";
        echo htmlspecialchars($message);
        echo "</div>";
    }

    /** @param list<string> $log */
    public static function updatePage(array $log): void
    {
        echo "<!DOCTYPE html>
    <html lang='fr'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Mise à jour base de données - DynamoMenu</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background: linear-gradient(180deg, #070707, #0b0b0d);
                color: #e6e6e6;
                min-height: 100vh;
                padding: 40px;
                margin: 0;
            }
            .container {
                max-width: 800px;
                margin: 0 auto;
                background: rgba(15, 15, 16, 0.8);
                padding: 30px;
                border-radius: 12px;
                border: 1px solid rgba(255, 255, 255, 0.1);
            }
            h1 { color: #ff6f1f; margin-bottom: 20px; }
            .success {
                background: rgba(40, 167, 69, 0.1);
                border: 1px solid rgba(40, 167, 69, 0.3);
                border-radius: 8px;
                padding: 15px;
                margin: 15px 0;
                color: #28a745;
            }
            .log {
                background: rgba(0, 0, 0, 0.2);
                padding: 15px;
                border-radius: 8px;
                margin: 10px 0;
                max-height: 300px;
                overflow-y: auto;
                font-family: monospace;
                font-size: 14px;
            }
            .btn {
                display: inline-block;
                background: linear-gradient(135deg, #ff6f1f, #ff8a3d);
                color: white;
                padding: 12px 24px;
                border-radius: 8px;
                text-decoration: none;
                font-weight: bold;
                margin-top: 20px;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <h1>🔄 Mise à jour de la base de données</h1>";

        echo "<div class='log'>";
        foreach ($log as $entry) {
            echo htmlspecialchars($entry) . '<br>';
        }
        echo '</div>';

        echo "<div class='success'>✅ Mise à jour terminée ! La base de données est maintenant compatible avec les nouvelles fonctionnalités.</div>";
        echo "<div style='margin-top: 20px;'>
            <a href='client/menu.php' class='btn'>Accéder au menu →</a>
            <a href='client/index.php' class='btn' style='background: #6c757d; margin-left: 10px;'>Retour à l'accueil</a>
          </div>";
        echo '</div></body></html>';
    }

    /** @param list<string> $log */
    public static function updateCompact(array $log): void
    {
        echo "<div style='background: linear-gradient(180deg, #070707, #0b0b0d); color: #e6e6e6; min-height: 100vh; padding: 40px; font-family: Arial, sans-serif;'>";
        echo "<div style='max-width: 700px; margin: 0 auto; background: rgba(15, 15, 16, 0.8); padding: 30px; border-radius: 12px; border: 1px solid rgba(255, 255, 255, 0.1);'>";
        echo "<h1 style='color: #ff6f1f; margin-bottom: 20px;'>🔄 Mise à jour de la base de données</h1>";
        echo "<h3 style='color: #fff; margin-top: 20px;'>Résultats de la mise à jour :</h3>";
        echo "<div style='background: rgba(0, 0, 0, 0.2); padding: 15px; border-radius: 8px; margin: 10px 0; max-height: 300px; overflow-y: auto;'>";
        foreach ($log as $update) {
            echo "<div style='padding: 5px 0; border-bottom: 1px solid rgba(255,255,255,0.1);'>" . htmlspecialchars($update) . '</div>';
        }
        echo '</div>';
        echo "<div style='background: rgba(40, 167, 69, 0.1); border: 1px solid rgba(40, 167, 69, 0.3); border-radius: 8px; padding: 15px; margin-top: 20px;'>";
        echo "<strong style='color: #28a745;'>✅ Mise à jour terminée !</strong><br>";
        echo 'La base de données a été mise à jour avec les nouvelles fonctionnalités.';
        echo '</div>';
        echo "<div style='margin-top: 20px;'>";
        echo "<a href='client/menu.php' style='display: inline-block; background: linear-gradient(135deg, #ff6f1f, #ff8a3d); color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; margin-right: 10px; font-weight: bold;'>Accéder au menu →</a>";
        echo "<a href='client/index.php' style='display: inline-block; background: #6c757d; color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: bold;'>Retour à l'accueil</a>";
        echo '</div></div></div>';
    }

    public static function connectionError(string $message): void
    {
        echo "<div class='container'>
            <h1>❌ Erreur de connexion</h1>
            <div class='error'>Impossible de se connecter à la base de données : " . htmlspecialchars($message) . "</div>
            <p>Vérifiez votre configuration dans config/db.php</p>
          </div>";
    }

    public static function cliSuccess(string $message): void
    {
        if (PHP_SAPI === 'cli') {
            echo $message . PHP_EOL;
        }
    }
}
