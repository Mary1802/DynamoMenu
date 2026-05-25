<?php
/**
 * Script d'initialisation de la base de données DynamoMenu
 * À exécuter une seule fois pour configurer les tables et les données de test
 */

session_start();

// Configuration
$db_config = require '../config/db.php';

try {
    $pdo = new PDO(
        "mysql:host=" . $db_config['host'],
        $db_config['user'],
        $db_config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Créer la base de données et l'utiliser
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . $db_config['dbname'] . "`");
    $pdo->exec("USE `" . $db_config['dbname'] . "`");

    // Table employés
    $pdo->exec("CREATE TABLE IF NOT EXISTS employe (
        id_employe INT PRIMARY KEY AUTO_INCREMENT,
        nom_employe VARCHAR(100) NOT NULL,
        prenom_employe VARCHAR(100) NOT NULL,
        email_employe VARCHAR(100) UNIQUE NOT NULL,
        mot_de_passe VARCHAR(255) NOT NULL,
        role ENUM('admin', 'cuisinier', 'caissier') NOT NULL,
        telephone_employe VARCHAR(20),
        date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Table clients
    $pdo->exec("CREATE TABLE IF NOT EXISTS client (
        id_client INT PRIMARY KEY AUTO_INCREMENT,
        nom_client VARCHAR(100) NOT NULL,
        points INT DEFAULT 0,
        telephone_client VARCHAR(20),
        date_inscription TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Table des tables de restaurant
    $pdo->exec("CREATE TABLE IF NOT EXISTS table_restaurant (
        num_table INT PRIMARY KEY,
        nombre_place INT NOT NULL
    )");

    // Table commandes
    $pdo->exec("CREATE TABLE IF NOT EXISTS commande (
        num_commande INT PRIMARY KEY AUTO_INCREMENT,
        id_client INT,
        num_table INT,
        date_commande TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        quantite_plats INT DEFAULT 0,
        quantite_boissons INT DEFAULT 0,
        statut ENUM('en_attente', 'en_preparation', 'prete', 'livree', 'annulee') DEFAULT 'en_attente',
        montant_total DECIMAL(10, 2) DEFAULT 0.00,
        FOREIGN KEY (id_client) REFERENCES client(id_client) ON DELETE SET NULL,
        FOREIGN KEY (num_table) REFERENCES table_restaurant(num_table) ON DELETE SET NULL
    )");

    // Table plats
    $pdo->exec("CREATE TABLE IF NOT EXISTS plat (
        id_plat INT PRIMARY KEY AUTO_INCREMENT,
        nom_plat VARCHAR(100) NOT NULL,
        prix_unitaire DECIMAL(10, 2) NOT NULL,
        categorie VARCHAR(50)
    )");

    // Table boissons
    $pdo->exec("CREATE TABLE IF NOT EXISTS boisson (
        id_boisson INT PRIMARY KEY AUTO_INCREMENT,
        nom_boisson VARCHAR(100) NOT NULL,
        dosage VARCHAR(100),
        quantite_boisson INT DEFAULT 0
    )");

    // Table contenant les détails de commandes
    $pdo->exec("CREATE TABLE IF NOT EXISTS contient (
        id_detail INT PRIMARY KEY AUTO_INCREMENT,
        num_commande INT NOT NULL,
        id_plat INT NULL,
        id_boisson INT NULL,
        quantite INT NOT NULL,
        prix DECIMAL(10, 2) NOT NULL,
        sous_total DECIMAL(10, 2) NOT NULL,
        FOREIGN KEY (num_commande) REFERENCES commande(num_commande) ON DELETE CASCADE,
        FOREIGN KEY (id_plat) REFERENCES plat(id_plat) ON DELETE SET NULL,
        FOREIGN KEY (id_boisson) REFERENCES boisson(id_boisson) ON DELETE SET NULL
    )");

    // Table facture
    $pdo->exec("CREATE TABLE IF NOT EXISTS facture (
        num_facture INT PRIMARY KEY AUTO_INCREMENT,
        num_commande INT UNIQUE,
        total_paye DECIMAL(10, 2) NOT NULL,
        date_facture TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (num_commande) REFERENCES commande(num_commande) ON DELETE CASCADE
    )");

    // Table logs d'activité
    $pdo->exec("CREATE TABLE IF NOT EXISTS log_activite (
        id_log INT PRIMARY KEY AUTO_INCREMENT,
        action VARCHAR(100) NOT NULL,
        description TEXT,
        date_action TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        module_concerne VARCHAR(100)
    )");

    // Insérer des employés de test
    $stmt = $pdo->prepare("INSERT IGNORE INTO employe (nom_employe, prenom_employe, email_employe, mot_de_passe, role, telephone_employe) VALUES (?, ?, ?, ?, ?, ?)");
    $employes = [
        ['Pierre', 'Dupont', 'pierre@dynamomenu.fr', 'chef123', 'cuisinier', '0612345678'],
        ['Marie', 'Curie', 'marie@dynamomenu.fr', 'chef123', 'cuisinier', '0612345679'],
        ['Jean', 'Martin', 'jean@dynamomenu.fr', 'caisse123', 'caissier', '0623456789'],
        ['Bob', 'Admin', 'admin@dynamomenu.fr', 'admin123', 'admin', '0634567890'],
    ];
    foreach ($employes as $emp) {
        $stmt->execute($emp);
    }

    // Insérer des clients de test
    $stmt = $pdo->prepare("INSERT IGNORE INTO client (nom_client, points, telephone_client) VALUES (?, ?, ?)");
    $clients = [
        ['Dupont Jean', 0, '0612345678'],
        ['Martin Sophie', 10, '0623456789'],
        ['Bernard Luc', 5, '0634567890'],
    ];
    foreach ($clients as $cli) {
        $stmt->execute($cli);
    }

    // Insérer des tables de restaurant
    $stmt = $pdo->prepare("INSERT IGNORE INTO table_restaurant (num_table, nombre_place) VALUES (?, ?)");
    $tables = [
        [1, 4],
        [2, 4],
        [3, 2],
        [4, 6],
        [5, 2],
    ];
    foreach ($tables as $table) {
        $stmt->execute($table);
    }

    // Insérer des plats de test
    $stmt = $pdo->prepare("INSERT IGNORE INTO plat (nom_plat, prix_unitaire, categorie) VALUES (?, ?, ?)");
    $plats = [
        ['Burger Classique', 8.50, 'Burgers'],
        ['Pizza Margherita', 12.00, 'Pizzas'],
        ['Salade Verte', 6.50, 'Salades'],
        ['Pâtes Carbonara', 10.00, 'Pâtes'],
    ];
    foreach ($plats as $plat) {
        $stmt->execute($plat);
    }

    // Insérer des boissons de test
    $stmt = $pdo->prepare("INSERT IGNORE INTO boisson (nom_boisson, dosage, quantite_boisson) VALUES (?, ?, ?)");
    $boissons = [
        ['Soda Frais', '33cl', 50],
        ['Eau Minérale', '50cl', 40],
        ['Jus de Fruit', '25cl', 30],
    ];
    foreach ($boissons as $boisson) {
        $stmt->execute($boisson);
    }

    // Insérer des commandes de test
    $pdo->exec("INSERT IGNORE INTO commande (id_client, num_table, quantite_plats, quantite_boissons, statut, montant_total) VALUES
        (1, 1, 2, 1, 'en_attente', 20.00),
        (2, 2, 1, 1, 'en_preparation', 18.50),
        (3, 3, 1, 1, 'prete', 14.50)");

    // Insérer les détails de commandes
    $pdo->exec("INSERT IGNORE INTO contient (num_commande, id_plat, id_boisson, quantite, prix, sous_total) VALUES
        (1, 1, NULL, 2, 8.50, 17.00),
        (1, NULL, 1, 1, 2.50, 2.50),
        (2, 2, NULL, 1, 12.00, 12.00),
        (2, NULL, 3, 1, 3.50, 3.50),
        (3, 3, NULL, 1, 6.50, 6.50),
        (3, NULL, 2, 1, 2.00, 2.00)");

    // Insérer une facture pour la commande prête
    $pdo->exec("INSERT IGNORE INTO facture (num_commande, total_paye) VALUES (3, 14.50)");

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
} catch (PDOException $e) {
    echo "<div style='background: #dc3545; color: white; padding: 20px; border-radius: 8px;'>";
    echo "<strong>Erreur d'initialisation :</strong><br>";
    echo htmlspecialchars($e->getMessage());
    echo "</div>";
}
