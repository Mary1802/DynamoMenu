<?php

declare(strict_types=1);

namespace App\Setup;

use App\Core\Application;
use PDO;
use PDOException;

/** Migrations legacy idempotentes (colonnes / données historiques). */
final class LegacyDatabaseUpdater
{
    public function __construct(
        private readonly Application $app,
    ) {
    }

    public static function fromApp(?Application $app = null): self
    {
        return new self($app ?? Application::getInstance());
    }

    /** @return list<string> */
    public function run(): array
    {
        $pdo = $this->app->db();
        $log = [];

        try {
            $this->app->schemaUpgrade()->run();
            $log[] = '✅ Schéma base : appliqué';
        } catch (PDOException $e) {
            $log[] = '❌ Schéma base : ' . $e->getMessage();
        }

        foreach ($this->legacyQueries() as $sql => $description) {
            $log[] = $this->execSafe($pdo, $sql, $description);
        }

        return $log;
    }

    /** @return array<string, string> */
    private function legacyQueries(): array
    {
        return [
            'ALTER TABLE client ADD COLUMN prenom_client VARCHAR(100) AFTER nom_client' => "Colonne 'prenom_client'",
            'ALTER TABLE client ADD COLUMN email_client VARCHAR(100) AFTER prenom_client' => "Colonne 'email_client'",
            "ALTER TABLE boisson ADD COLUMN type_boisson ENUM('soda', 'eau', 'jus', 'alcool') DEFAULT 'soda' AFTER nom_boisson" => "Colonne 'type_boisson'",
            "ALTER TABLE boisson ADD COLUMN options_fruits VARCHAR(255) DEFAULT '' AFTER quantite_boisson" => "Colonne 'options_fruits'",
            "ALTER TABLE contient ADD COLUMN sauces VARCHAR(255) DEFAULT '' AFTER sous_total" => "Colonne 'sauces'",
            "ALTER TABLE contient ADD COLUMN personnalisation_boisson VARCHAR(255) DEFAULT '' AFTER sauces" => "Colonne 'personnalisation_boisson'",
            "ALTER TABLE facture ADD COLUMN mode_paiement ENUM('carte', 'especes', 'mobile') NOT NULL AFTER total_paye" => "Colonne 'mode_paiement'",
            'ALTER TABLE table_restaurant ADD COLUMN code_table VARCHAR(32) NULL UNIQUE AFTER num_table' => "Colonne table 'code_table'",
            'ALTER TABLE table_restaurant ADD COLUMN actif TINYINT(1) NOT NULL DEFAULT 1' => "Colonne table 'actif'",
            'ALTER TABLE table_restaurant ADD COLUMN libelle VARCHAR(100) NULL AFTER nombre_place' => "Colonne table 'libelle'",
            "ALTER TABLE commande ADD COLUMN mode_paiement_souhaite ENUM('especes','mobile_money') NULL AFTER montant_total" => "Colonne commande 'mode_paiement_souhaite'",
            'ALTER TABLE commande ADD COLUMN instructions_speciales TEXT NULL AFTER mode_paiement_souhaite' => "Colonne commande 'instructions_speciales'",
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
            "UPDATE boisson SET type_boisson = 'soda' WHERE nom_boisson LIKE '%Soda%' OR nom_boisson LIKE '%Coca%'" => 'Mise à jour type soda',
            "UPDATE boisson SET type_boisson = 'eau' WHERE nom_boisson LIKE '%Eau%'" => 'Mise à jour type eau',
            "UPDATE boisson SET type_boisson = 'jus' WHERE nom_boisson LIKE '%Jus%'" => 'Mise à jour type jus',
            "UPDATE boisson SET options_fruits = 'orange,pomme,ananas,mangue' WHERE nom_boisson = 'Jus de Fruit'" => 'Options Jus de Fruit',
            "UPDATE boisson SET options_fruits = 'orange' WHERE nom_boisson = 'Jus d\\'Orange'" => "Options Jus d'Orange",
            "UPDATE boisson SET options_fruits = 'pomme' WHERE nom_boisson = 'Jus de Pomme'" => 'Options Jus de Pomme',
            "INSERT IGNORE INTO boisson (nom_boisson, type_boisson, dosage, quantite_boisson, options_fruits) VALUES ('Coca-Cola', 'soda', '33cl', 50, '')" => 'Ajout Coca-Cola',
            "INSERT IGNORE INTO boisson (nom_boisson, type_boisson, dosage, quantite_boisson, options_fruits) VALUES ('Jus d\\'Orange', 'jus', '25cl', 30, 'orange')" => "Ajout Jus d'Orange",
            "INSERT IGNORE INTO boisson (nom_boisson, type_boisson, dosage, quantite_boisson, options_fruits) VALUES ('Jus de Pomme', 'jus', '25cl', 30, 'pomme')" => 'Ajout Jus de Pomme',
            "UPDATE client SET prenom_client = 'Jean', email_client = 'jean.dupont@email.com' WHERE nom_client = 'Dupont'" => 'Mise à jour client Dupont',
            "UPDATE client SET prenom_client = 'Sophie', email_client = 'sophie.martin@email.com' WHERE nom_client = 'Martin'" => 'Mise à jour client Martin',
            "UPDATE client SET prenom_client = 'Luc', email_client = 'luc.bernard@email.com' WHERE nom_client = 'Bernard'" => 'Mise à jour client Bernard',
            "UPDATE facture SET mode_paiement = 'carte' WHERE mode_paiement IS NULL OR mode_paiement = ''" => 'Mise à jour mode paiement factures',
        ];
    }

    private function execSafe(PDO $pdo, string $sql, string $description): string
    {
        try {
            $pdo->exec($sql);

            return "✅ {$description} : Succès";
        } catch (PDOException $e) {
            $msg = $e->getMessage();
            if (str_contains($msg, 'Duplicate column name')
                || str_contains($msg, 'Table already exists')
                || str_contains($msg, 'Duplicate entry')) {
                return "⚠️ {$description} : Existe déjà";
            }

            return "❌ {$description} : Erreur - {$msg}";
        }
    }
}
