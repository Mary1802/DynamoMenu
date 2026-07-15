<?php

declare(strict_types=1);

namespace App\Setup;

use App\Core\Application;
final class DatabaseInitializer
{
    public function __construct(
        private readonly Application $app,
    ) {
    }

    public static function fromApp(?Application $app = null): self
    {
        return new self($app ?? Application::getInstance());
    }

    public function run(): void
    {
        $db = $this->app->config()->database();

        $pdo = new PDO(
            'mysql:host=' . $db['host'],
            $db['user'],
            $db['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . $db['dbname'] . '`');
        $pdo->exec('USE `' . $db['dbname'] . '`');

        $this->createTables($pdo);
        $this->seedAdmin($pdo);
        $this->seedDemoData($pdo);

        $this->app->schemaUpgrade()->run();
    }

    private function createTables(PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS employe (
        id_employe INT PRIMARY KEY AUTO_INCREMENT,
        nom_employe VARCHAR(100) NOT NULL,
        prenom_employe VARCHAR(100) NOT NULL,
        email_employe VARCHAR(100) UNIQUE NOT NULL,
        mot_de_passe VARCHAR(255) NOT NULL,
        role ENUM('admin', 'cuisinier', 'caissier', 'manager') NOT NULL,
        telephone_employe VARCHAR(20),
        date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS client (
        id_client INT PRIMARY KEY AUTO_INCREMENT,
        nom_client VARCHAR(100) NOT NULL,
        prenom_client VARCHAR(100),
        email_client VARCHAR(100),
        telephone_client VARCHAR(20),
        date_inscription TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_client_email (email_client),
        UNIQUE KEY uq_client_telephone (telephone_client)
    )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS table_restaurant (
        num_table INT PRIMARY KEY,
        nombre_place INT NOT NULL,
        libelle VARCHAR(100) NULL,
        code_table VARCHAR(32) NULL UNIQUE,
        actif TINYINT(1) NOT NULL DEFAULT 1
    )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS commande (
        num_commande INT PRIMARY KEY AUTO_INCREMENT,
        id_client INT,
        num_table INT,
        date_commande TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        statut ENUM('en_attente', 'en_preparation', 'prete', 'livree', 'annulee') DEFAULT 'en_attente',
        montant_total DECIMAL(10, 2) DEFAULT 0.00,
        mode_paiement_souhaite ENUM('especes', 'mobile_money') NULL,
        instructions_speciales TEXT NULL,
        FOREIGN KEY (id_client) REFERENCES client(id_client) ON DELETE SET NULL,
        FOREIGN KEY (num_table) REFERENCES table_restaurant(num_table) ON DELETE SET NULL
    )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS plat (
        id_plat INT PRIMARY KEY AUTO_INCREMENT,
        nom_plat VARCHAR(100) NOT NULL,
        prix_unitaire DECIMAL(10, 2) NOT NULL,
        categorie VARCHAR(50)
    )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS boisson (
        id_boisson INT PRIMARY KEY AUTO_INCREMENT,
        nom_boisson VARCHAR(100) NOT NULL,
        type_boisson ENUM('soda', 'eau', 'jus', 'alcool') DEFAULT 'soda',
        dosage VARCHAR(100),
        quantite_boisson INT DEFAULT 0,
        options_fruits VARCHAR(255) DEFAULT ''
    )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS contient (
        id_detail INT PRIMARY KEY AUTO_INCREMENT,
        num_commande INT NOT NULL,
        id_plat INT NULL,
        id_boisson INT NULL,
        quantite INT NOT NULL,
        prix DECIMAL(10, 2) NOT NULL,
        sous_total DECIMAL(10, 2) NOT NULL,
        sauces VARCHAR(255) DEFAULT '',
        personnalisation_boisson VARCHAR(255) DEFAULT '',
        FOREIGN KEY (num_commande) REFERENCES commande(num_commande) ON DELETE CASCADE,
        FOREIGN KEY (id_plat) REFERENCES plat(id_plat) ON DELETE SET NULL,
        FOREIGN KEY (id_boisson) REFERENCES boisson(id_boisson) ON DELETE SET NULL
    )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS facture (
        num_facture INT PRIMARY KEY AUTO_INCREMENT,
        num_commande INT UNIQUE,
        total_paye DECIMAL(10, 2) NOT NULL,
        mode_paiement ENUM('carte', 'especes', 'mobile') NOT NULL,
        date_facture TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (num_commande) REFERENCES commande(num_commande) ON DELETE CASCADE
    )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS log_activite (
        id_log INT PRIMARY KEY AUTO_INCREMENT,
        action VARCHAR(100) NOT NULL,
        description TEXT,
        date_action TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        module_concerne VARCHAR(100)
    )");
    }

    private function seedAdmin(PDO $pdo): void
    {
        $employeCount = (int) $pdo->query('SELECT COUNT(*) FROM employe')->fetchColumn();
        if ($employeCount !== 0) {
            return;
        }

        $this->app->employePasswordService()->ensureColumn();
        $this->app->employeRepository()->create(
            'Admin',
            'Principal',
            'admin@dynamomenu.fr',
            $this->app->passwordHasher()->hash('admin123'),
            'admin',
            '',
            'admin123'
        );
    }

    private function seedDemoData(PDO $pdo): void
    {
        $stmt = $pdo->prepare('INSERT IGNORE INTO client (nom_client, prenom_client, email_client, telephone_client) VALUES (?, ?, ?, ?)');
        foreach ([
            ['Dupont', 'Jean', 'jean.dupont@email.com', '0612345678'],
            ['Martin', 'Sophie', 'sophie.martin@email.com', '0623456789'],
            ['Bernard', 'Luc', 'luc.bernard@email.com', '0634567890'],
        ] as $cli) {
            $stmt->execute($cli);
        }

        $stmt = $pdo->prepare('INSERT IGNORE INTO table_restaurant (num_table, nombre_place) VALUES (?, ?)');
        foreach ([[1, 4], [2, 4], [3, 2], [4, 6], [5, 2]] as $table) {
            $stmt->execute($table);
        }

        $stmt = $pdo->prepare('INSERT IGNORE INTO plat (nom_plat, prix_unitaire, categorie) VALUES (?, ?, ?)');
        foreach ([
            ['Burger Classique', 8.50, 'Burgers'],
            ['Pizza Margherita', 12.00, 'Pizzas'],
            ['Salade Verte', 6.50, 'Salades'],
            ['Pâtes Carbonara', 10.00, 'Pâtes'],
        ] as $plat) {
            $stmt->execute($plat);
        }

        $stmt = $pdo->prepare('INSERT IGNORE INTO boisson (nom_boisson, type_boisson, dosage, quantite_boisson, options_fruits) VALUES (?, ?, ?, ?, ?)');
        foreach ([
            ['Soda Frais', 'soda', '33cl', 50, ''],
            ['Eau Minérale', 'eau', '50cl', 40, ''],
            ['Jus de Fruit', 'jus', '25cl', 30, 'Orange,Banane,Pomme,Ananas,Mangue,Fraise'],
            ['Coca-Cola', 'soda', '33cl', 50, ''],
            ['Jus d\'Orange', 'jus', '25cl', 30, 'Orange'],
            ['Jus de Pomme', 'jus', '25cl', 30, 'Pomme'],
        ] as $boisson) {
            $stmt->execute($boisson);
        }

        $pdo->exec("INSERT IGNORE INTO commande (id_client, num_table, statut, montant_total) VALUES
        (1, 1, 'en_attente', 20.00),
        (2, 2, 'en_preparation', 18.50),
        (3, 3, 'prete', 14.50)");

        $pdo->exec("INSERT IGNORE INTO contient (num_commande, id_plat, id_boisson, quantite, prix, sous_total) VALUES
        (1, 1, NULL, 2, 8.50, 17.00),
        (1, NULL, 1, 1, 2.50, 2.50),
        (2, 2, NULL, 1, 12.00, 12.00),
        (2, NULL, 3, 1, 3.50, 3.50),
        (3, 3, NULL, 1, 6.50, 6.50),
        (3, NULL, 2, 1, 2.00, 2.00)");

        $pdo->exec('INSERT IGNORE INTO facture (num_commande, total_paye) VALUES (3, 14.50)');
    }
}
