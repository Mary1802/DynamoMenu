<?php
/**
 * Script de mise à jour de la base de données DynamoMenu
 * Ajoute les nouvelles colonnes et tables nécessaires
 */

require_once __DIR__ . '/includes/setup_guard.php';
require_once __DIR__ . '/includes/staff_auth.php';
setup_require_access();
staff_session_start();

// Configuration
$db_config = require 'config/db.php';

try {
    $pdo = new PDO(
        "mysql:host=" . $db_config['host'] . ";dbname=" . $db_config['dbname'],
        $db_config['user'],
        $db_config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "<div style='background: linear-gradient(180deg, #070707, #0b0b0d); color: #e6e6e6; min-height: 100vh; padding: 40px; font-family: Arial, sans-serif;'>";
    echo "<div style='max-width: 700px; margin: 0 auto; background: rgba(15, 15, 16, 0.8); padding: 30px; border-radius: 12px; border: 1px solid rgba(255, 255, 255, 0.1);'>";
    echo "<h1 style='color: #ff6f1f; margin-bottom: 20px;'>🔄 Mise à jour de la base de données</h1>";

    // Vérifier et ajouter les colonnes manquantes
    $updates = [];

    // 1. Table client
    try {
        $pdo->exec("ALTER TABLE client ADD COLUMN prenom_client VARCHAR(100) AFTER nom_client");
        $updates[] = "✓ Colonne 'prenom_client' ajoutée à la table client";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') === false) {
            $updates[] = "⚠️ Erreur avec prenom_client: " . $e->getMessage();
        } else {
            $updates[] = "✓ Colonne 'prenom_client' existe déjà";
        }
    }

    try {
        $pdo->exec("ALTER TABLE client ADD COLUMN email_client VARCHAR(100) AFTER prenom_client");
        $updates[] = "✓ Colonne 'email_client' ajoutée à la table client";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') === false) {
            $updates[] = "⚠️ Erreur avec email_client: " . $e->getMessage();
        } else {
            $updates[] = "✓ Colonne 'email_client' existe déjà";
        }
    }

    // 2. Table boisson
    try {
        $pdo->exec("ALTER TABLE boisson ADD COLUMN type_boisson ENUM('soda', 'eau', 'jus', 'alcool') DEFAULT 'soda' AFTER nom_boisson");
        $updates[] = "✓ Colonne 'type_boisson' ajoutée à la table boisson";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') === false) {
            $updates[] = "⚠️ Erreur avec type_boisson: " . $e->getMessage();
        } else {
            $updates[] = "✓ Colonne 'type_boisson' existe déjà";
        }
    }

    try {
        $pdo->exec("ALTER TABLE boisson ADD COLUMN options_fruits VARCHAR(255) DEFAULT '' AFTER quantite_boisson");
        $updates[] = "✓ Colonne 'options_fruits' ajoutée à la table boisson";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') === false) {
            $updates[] = "⚠️ Erreur avec options_fruits: " . $e->getMessage();
        } else {
            $updates[] = "✓ Colonne 'options_fruits' existe déjà";
        }
    }

    // 3. Table contient
    try {
        $pdo->exec("ALTER TABLE contient ADD COLUMN sauces VARCHAR(255) DEFAULT '' AFTER sous_total");
        $updates[] = "✓ Colonne 'sauces' ajoutée à la table contient";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') === false) {
            $updates[] = "⚠️ Erreur avec sauces: " . $e->getMessage();
        } else {
            $updates[] = "✓ Colonne 'sauces' existe déjà";
        }
    }

    try {
        $pdo->exec("ALTER TABLE contient ADD COLUMN personnalisation_boisson VARCHAR(255) DEFAULT '' AFTER sauces");
        $updates[] = "✓ Colonne 'personnalisation_boisson' ajoutée à la table contient";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') === false) {
            $updates[] = "⚠️ Erreur avec personnalisation_boisson: " . $e->getMessage();
        } else {
            $updates[] = "✓ Colonne 'personnalisation_boisson' existe déjà";
        }
    }

    // 4. Table facture
    try {
        $pdo->exec("ALTER TABLE facture ADD COLUMN mode_paiement ENUM('carte', 'especes', 'mobile') NOT NULL AFTER total_paye");
        $updates[] = "✓ Colonne 'mode_paiement' ajoutée à la table facture";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') === false) {
            $updates[] = "⚠️ Erreur avec mode_paiement: " . $e->getMessage();
        } else {
            $updates[] = "✓ Colonne 'mode_paiement' existe déjà";
        }
    }

    // 5. Créer la table demande_paiement
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS demande_paiement (
            id_demande INT PRIMARY KEY AUTO_INCREMENT,
            num_commande INT NOT NULL,
            mode_paiement ENUM('carte', 'especes', 'mobile') NOT NULL,
            montant DECIMAL(10, 2) NOT NULL,
            statut ENUM('en_attente', 'traitee', 'annulee') DEFAULT 'en_attente',
            date_demande TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            date_traitement TIMESTAMP NULL,
            FOREIGN KEY (num_commande) REFERENCES commande(num_commande) ON DELETE CASCADE
        )");
        $updates[] = "✓ Table 'demande_paiement' créée";
    } catch (PDOException $e) {
        $updates[] = "✓ Table 'demande_paiement' existe déjà";
    }

    // Mettre à jour les données existantes
    // Mettre à jour le type_boisson pour les boissons existantes
    try {
        $pdo->exec("UPDATE boisson SET type_boisson = 'soda' WHERE nom_boisson LIKE '%Soda%' OR nom_boisson LIKE '%Coca%'");
        $pdo->exec("UPDATE boisson SET type_boisson = 'eau' WHERE nom_boisson LIKE '%Eau%'");
        $pdo->exec("UPDATE boisson SET type_boisson = 'jus' WHERE nom_boisson LIKE '%Jus%'");
        $updates[] = "✓ Types de boissons mis à jour";
    } catch (PDOException $e) {
        $updates[] = "⚠️ Erreur lors de la mise à jour des types: " . $e->getMessage();
    }

    // Mettre à jour les options_fruits pour les jus
    try {
        $pdo->exec("UPDATE boisson SET options_fruits = 'orange,pomme,ananas,mangue' WHERE nom_boisson = 'Jus de Fruit'");
        $pdo->exec("UPDATE boisson SET options_fruits = 'orange' WHERE nom_boisson = 'Jus d\\'Orange'");
        $pdo->exec("UPDATE boisson SET options_fruits = 'pomme' WHERE nom_boisson = 'Jus de Pomme'");
        $updates[] = "✓ Options de fruits mises à jour";
    } catch (PDOException $e) {
        $updates[] = "⚠️ Erreur lors de la mise à jour des options: " . $e->getMessage();
    }

    // Ajouter de nouvelles boissons si nécessaire
    $new_drinks = [
        ['Coca-Cola', 'soda', '33cl', 50, ''],
        ['Jus d\'Orange', 'jus', '25cl', 30, 'orange'],
        ['Jus de Pomme', 'jus', '25cl', 30, 'pomme'],
    ];

    foreach ($new_drinks as $drink) {
        try {
            $stmt = $pdo->prepare("INSERT IGNORE INTO boisson (nom_boisson, type_boisson, dosage, quantite_boisson, options_fruits) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute($drink);
        } catch (PDOException $e) {
            // Ignorer les erreurs d'insertion
        }
    }
    $updates[] = "✓ Nouvelles boissons ajoutées";

    // Mettre à jour les clients existants
    try {
        $pdo->exec("UPDATE client SET prenom_client = 'Jean', email_client = 'jean.dupont@email.com' WHERE nom_client = 'Dupont'");
        $pdo->exec("UPDATE client SET prenom_client = 'Sophie', email_client = 'sophie.martin@email.com' WHERE nom_client = 'Martin'");
        $pdo->exec("UPDATE client SET prenom_client = 'Luc', email_client = 'luc.bernard@email.com' WHERE nom_client = 'Bernard'");
        $updates[] = "✓ Informations clients mises à jour";
    } catch (PDOException $e) {
        $updates[] = "⚠️ Erreur lors de la mise à jour des clients: " . $e->getMessage();
    }

    // Afficher les résultats
    echo "<h3 style='color: #fff; margin-top: 20px;'>Résultats de la mise à jour :</h3>";
    echo "<div style='background: rgba(0, 0, 0, 0.2); padding: 15px; border-radius: 8px; margin: 10px 0; max-height: 300px; overflow-y: auto;'>";
    foreach ($updates as $update) {
        echo "<div style='padding: 5px 0; border-bottom: 1px solid rgba(255,255,255,0.1);'>" . htmlspecialchars($update) . "</div>";
    }
    echo "</div>";

    echo "<div style='background: rgba(40, 167, 69, 0.1); border: 1px solid rgba(40, 167, 69, 0.3); border-radius: 8px; padding: 15px; margin-top: 20px;'>";
    echo "<strong style='color: #28a745;'>✅ Mise à jour terminée !</strong><br>";
    echo "La base de données a été mise à jour avec les nouvelles fonctionnalités.";
    echo "</div>";

    echo "<div style='margin-top: 20px;'>";
    echo "<a href='client/menu.php' style='display: inline-block; background: linear-gradient(135deg, #ff6f1f, #ff8a3d); color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; margin-right: 10px; font-weight: bold;'>Accéder au menu →</a>";
    echo "<a href='client/index.php' style='display: inline-block; background: #6c757d; color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: bold;'>Retour à l'accueil</a>";
    echo "</div>";

    echo "</div></div>";

} catch (PDOException $e) {
    echo "<div style='background: #dc3545; color: white; padding: 20px; border-radius: 8px;'>";
    echo "<strong>Erreur de mise à jour :</strong><br>";
    echo htmlspecialchars($e->getMessage());
    echo "</div>";
}