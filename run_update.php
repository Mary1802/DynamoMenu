<?php
/**
 * Script pour exécuter les mises à jour de la base de données
 */

session_start();

// Configuration
$db_config = require 'config/db.php';

try {
    $pdo = new PDO(
        "mysql:host=" . $db_config['host'] . ";dbname=" . $db_config['dbname'],
        $db_config['user'],
        $db_config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

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
            h1 {
                color: #ff6f1f;
                margin-bottom: 20px;
            }
            .success {
                background: rgba(40, 167, 69, 0.1);
                border: 1px solid rgba(40, 167, 69, 0.3);
                border-radius: 8px;
                padding: 15px;
                margin: 15px 0;
                color: #28a745;
            }
            .error {
                background: rgba(220, 53, 69, 0.1);
                border: 1px solid rgba(220, 53, 69, 0.3);
                border-radius: 8px;
                padding: 15px;
                margin: 15px 0;
                color: #dc3545;
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
                border: none;
                cursor: pointer;
            }
            .btn:hover {
                background: linear-gradient(135deg, #ff8a3d, #ff6f1f);
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
        </style>
    </head>
    <body>
        <div class='container'>
            <h1>🔄 Mise à jour de la base de données</h1>";

    $log = [];

    require_once __DIR__ . '/includes/schema_upgrade.php';
    try {
        schema_upgrade($pdo);
        $log[] = '✅ Schéma fidélité & notifications : appliqué';
    } catch (PDOException $e) {
        $log[] = '❌ Schéma fidélité : ' . $e->getMessage();
    }
    
    // Liste des requêtes à exécuter
    $queries = [
        // 1. Table client
        "ALTER TABLE client ADD COLUMN prenom_client VARCHAR(100) AFTER nom_client" => "Colonne 'prenom_client'",
        "ALTER TABLE client ADD COLUMN email_client VARCHAR(100) AFTER prenom_client" => "Colonne 'email_client'",
        
        // 2. Table boisson
        "ALTER TABLE boisson ADD COLUMN type_boisson ENUM('soda', 'eau', 'jus', 'alcool') DEFAULT 'soda' AFTER nom_boisson" => "Colonne 'type_boisson'",
        "ALTER TABLE boisson ADD COLUMN options_fruits VARCHAR(255) DEFAULT '' AFTER quantite_boisson" => "Colonne 'options_fruits'",
        
        // 3. Table contient
        "ALTER TABLE contient ADD COLUMN sauces VARCHAR(255) DEFAULT '' AFTER sous_total" => "Colonne 'sauces'",
        "ALTER TABLE contient ADD COLUMN personnalisation_boisson VARCHAR(255) DEFAULT '' AFTER sauces" => "Colonne 'personnalisation_boisson'",
        
        // 4. Table facture
        "ALTER TABLE facture ADD COLUMN mode_paiement ENUM('carte', 'especes', 'mobile') NOT NULL AFTER total_paye" => "Colonne 'mode_paiement'",
        
        "ALTER TABLE table_restaurant ADD COLUMN code_table VARCHAR(32) NULL UNIQUE AFTER num_table" => "Colonne table 'code_table'",
        "ALTER TABLE table_restaurant ADD COLUMN actif TINYINT(1) NOT NULL DEFAULT 1" => "Colonne table 'actif'",
        "ALTER TABLE table_restaurant ADD COLUMN libelle VARCHAR(100) NULL AFTER nombre_place" => "Colonne table 'libelle'",
        "ALTER TABLE commande ADD COLUMN mode_paiement_souhaite ENUM('especes','mobile_money') NULL AFTER montant_total" => "Colonne commande 'mode_paiement_souhaite'",

        // 5. Table demande_paiement
        "CREATE TABLE IF NOT EXISTS demande_paiement (
            id_demande INT PRIMARY KEY AUTO_INCREMENT,
            num_commande INT NOT NULL,
            mode_paiement ENUM('carte', 'especes', 'mobile') NOT NULL,
            montant DECIMAL(10, 2) NOT NULL,
            statut ENUM('en_attente', 'traitee', 'annulee') DEFAULT 'en_attente',
            date_demande TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            date_traitement TIMESTAMP NULL,
            FOREIGN KEY (num_commande) REFERENCES commande(num_commande) ON DELETE CASCADE
        )" => "Table 'demande_paiement'",
        
        // 6. Mise à jour des types de boissons
        "UPDATE boisson SET type_boisson = 'soda' WHERE nom_boisson LIKE '%Soda%' OR nom_boisson LIKE '%Coca%'" => "Mise à jour type soda",
        "UPDATE boisson SET type_boisson = 'eau' WHERE nom_boisson LIKE '%Eau%'" => "Mise à jour type eau",
        "UPDATE boisson SET type_boisson = 'jus' WHERE nom_boisson LIKE '%Jus%'" => "Mise à jour type jus",
        
        // 7. Mise à jour des options de fruits
        "UPDATE boisson SET options_fruits = 'orange,pomme,ananas,mangue' WHERE nom_boisson = 'Jus de Fruit'" => "Options Jus de Fruit",
        "UPDATE boisson SET options_fruits = 'orange' WHERE nom_boisson = 'Jus d\\'Orange'" => "Options Jus d'Orange",
        "UPDATE boisson SET options_fruits = 'pomme' WHERE nom_boisson = 'Jus de Pomme'" => "Options Jus de Pomme",
        
        // 8. Ajout de nouvelles boissons
        "INSERT IGNORE INTO boisson (nom_boisson, type_boisson, dosage, quantite_boisson, options_fruits) VALUES ('Coca-Cola', 'soda', '33cl', 50, '')" => "Ajout Coca-Cola",
        "INSERT IGNORE INTO boisson (nom_boisson, type_boisson, dosage, quantite_boisson, options_fruits) VALUES ('Jus d\\'Orange', 'jus', '25cl', 30, 'orange')" => "Ajout Jus d'Orange",
        "INSERT IGNORE INTO boisson (nom_boisson, type_boisson, dosage, quantite_boisson, options_fruits) VALUES ('Jus de Pomme', 'jus', '25cl', 30, 'pomme')" => "Ajout Jus de Pomme",
        
        // 9. Mise à jour des clients
        "UPDATE client SET prenom_client = 'Jean', email_client = 'jean.dupont@email.com' WHERE nom_client = 'Dupont'" => "Mise à jour client Dupont",
        "UPDATE client SET prenom_client = 'Sophie', email_client = 'sophie.martin@email.com' WHERE nom_client = 'Martin'" => "Mise à jour client Martin",
        "UPDATE client SET prenom_client = 'Luc', email_client = 'luc.bernard@email.com' WHERE nom_client = 'Bernard'" => "Mise à jour client Bernard",
        
        // 10. Mise à jour des factures existantes
        "UPDATE facture SET mode_paiement = 'carte' WHERE mode_paiement IS NULL OR mode_paiement = ''" => "Mise à jour mode paiement factures",
    ];

    // Exécuter les requêtes
    foreach ($queries as $sql => $description) {
        try {
            $pdo->exec($sql);
            $log[] = "✅ $description : Succès";
        } catch (PDOException $e) {
            // Vérifier si c'est une erreur de colonne/table déjà existante
            if (strpos($e->getMessage(), 'Duplicate column name') !== false || 
                strpos($e->getMessage(), 'Table already exists') !== false ||
                strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $log[] = "⚠️ $description : Existe déjà";
            } else {
                $log[] = "❌ $description : Erreur - " . $e->getMessage();
            }
        }
    }

    // Afficher le log
    echo "<div class='log'>";
    foreach ($log as $entry) {
        echo htmlspecialchars($entry) . "<br>";
    }
    echo "</div>";

    echo "<div class='success'>✅ Mise à jour terminée ! La base de données est maintenant compatible avec les nouvelles fonctionnalités.</div>";

    echo "<div style='margin-top: 20px;'>
            <a href='client/menu.php' class='btn'>Accéder au menu →</a>
            <a href='client/index.php' class='btn' style='background: #6c757d; margin-left: 10px;'>Retour à l'accueil</a>
          </div>";

    echo "</div></body></html>";

} catch (PDOException $e) {
    echo "<div class='container'>
            <h1>❌ Erreur de connexion</h1>
            <div class='error'>Impossible de se connecter à la base de données : " . htmlspecialchars($e->getMessage()) . "</div>
            <p>Vérifiez votre configuration dans config/db.php</p>
          </div>";
}