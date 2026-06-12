<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Application;
use PDO;
use PDOException;

final class FactureRepository extends BaseRepository
{
    public function ensureSchema(): void
    {
        Application::getInstance()->tableRepository()->ensureSchema();
        require_once dirname(__DIR__, 3) . '/includes/money.php';
        contient_ensure_schema($this->pdo);

        $commandeCols = $this->columnNames('commande');
        if (!in_array('mode_paiement_souhaite', $commandeCols, true)) {
            $this->pdo->exec(
                "ALTER TABLE commande ADD COLUMN mode_paiement_souhaite ENUM('especes','mobile_money') NULL AFTER montant_total"
            );
        }

        $factureCols = $this->columnNames('facture');
        if (!in_array('mode_paiement', $factureCols, true)) {
            $this->pdo->exec(
                "ALTER TABLE facture ADD COLUMN mode_paiement ENUM('carte', 'especes', 'mobile') NOT NULL DEFAULT 'especes' AFTER total_paye"
            );
        }
    }

    public function create(int $numCommande, float $montantPaye, string $modePaiement): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO facture (num_commande, total_paye, mode_paiement) VALUES (?, ?, ?)'
        );
        $stmt->execute([$numCommande, $montantPaye, $modePaiement]);

        return (int) $this->pdo->lastInsertId();
    }

    public function markDemandesTraitees(int $numCommande): void
    {
        try {
            $stmt = $this->pdo->prepare(
                "UPDATE demande_paiement SET statut = 'traitee', date_traitement = NOW()
                 WHERE num_commande = ? AND statut = 'en_attente'"
            );
            $stmt->execute([$numCommande]);
        } catch (PDOException) {
            // Table absente sur anciennes installations
        }
    }

    public function cancelDemande(int $demandeId): void
    {
        try {
            $stmt = $this->pdo->prepare(
                "UPDATE demande_paiement SET statut = 'annulee', date_traitement = NOW() WHERE id_demande = ?"
            );
            $stmt->execute([$demandeId]);
        } catch (PDOException) {
            // Table absente
        }
    }

    public function hasFacture(int $numCommande): bool
    {
        $stmt = $this->pdo->prepare('SELECT num_facture FROM facture WHERE num_commande = ? LIMIT 1');
        $stmt->execute([$numCommande]);

        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /** @return array<string, mixed>|null */
    public function findByCommande(int $numCommande): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM facture WHERE num_commande = ? ORDER BY date_facture DESC LIMIT 1'
        );
        $stmt->execute([$numCommande]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    public function hasPendingDemande(int $numCommande): bool
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT id_demande FROM demande_paiement WHERE num_commande = ? AND statut = 'en_attente' LIMIT 1"
            );
            $stmt->execute([$numCommande]);

            return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException) {
            return false;
        }
    }

    public function createDemande(int $numCommande, string $modePaiement, float $montant): void
    {
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO demande_paiement (num_commande, mode_paiement, montant) VALUES (?, ?, ?)'
            );
            $stmt->execute([$numCommande, $modePaiement, $montant]);
        } catch (PDOException) {
            // Table absente sur anciennes installations
        }
    }

    /** @return list<array<string, mixed>> */
    public function findPendingDemandes(): array
    {
        try {
            $stmt = $this->pdo->query("SHOW TABLES LIKE 'demande_paiement'");
            if ($stmt->fetchColumn() === false) {
                return [];
            }

            $stmt = $this->pdo->prepare("
                SELECT
                    d.*,
                    c.montant_total,
                    c.date_commande,
                    t.num_table,
                    cl.nom_client,
                    cl.prenom_client,
                    cl.telephone_client
                FROM demande_paiement d
                JOIN commande c ON d.num_commande = c.num_commande
                LEFT JOIN table_restaurant t ON c.num_table = t.num_table
                LEFT JOIN client cl ON c.id_client = cl.id_client
                WHERE d.statut = 'en_attente'
                ORDER BY d.date_demande ASC
            ");
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException) {
            return [];
        }
    }

    /** @return list<array<string, mixed>> */
    public function findRecent(int $limit = 5): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                f.*,
                c.num_commande,
                c.montant_total,
                t.num_table,
                cl.nom_client,
                cl.prenom_client,
                cl.telephone_client
            FROM facture f
            JOIN commande c ON f.num_commande = c.num_commande
            LEFT JOIN table_restaurant t ON c.num_table = t.num_table
            LEFT JOIN client cl ON c.id_client = cl.id_client
            ORDER BY f.date_facture DESC
            LIMIT " . (int) $limit
        );
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return array{total_paiements:int, total_ca:float} */
    public function todayStats(): array
    {
        $defaults = ['total_paiements' => 0, 'total_ca' => 0.0];

        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) AS total_paiements, COALESCE(SUM(total_paye), 0) AS total_ca
                FROM facture
                WHERE DATE(date_facture) = CURDATE()
            ");
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                return $defaults;
            }

            return [
                'total_paiements' => (int) $row['total_paiements'],
                'total_ca' => (float) $row['total_ca'],
            ];
        } catch (PDOException) {
            return $defaults;
        }
    }

    /** @return array<string, mixed>|null */
    public function findWithDetails(int $numFacture): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                f.*,
                c.num_commande,
                c.date_commande,
                c.montant_total,
                c.statut,
                t.num_table,
                cl.nom_client,
                cl.prenom_client,
                cl.email_client,
                cl.telephone_client
            FROM facture f
            JOIN commande c ON f.num_commande = c.num_commande
            LEFT JOIN table_restaurant t ON c.num_table = t.num_table
            LEFT JOIN client cl ON c.id_client = cl.id_client
            WHERE f.num_facture = ?
        ");
        $stmt->execute([$numFacture]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /** @return list<array<string, mixed>> */
    public function fetchInvoiceArticles(int $numCommande): array
    {
        $boissonColumns = $this->columnNames('boisson');
        $typeBoissonTableExists = count(
            $this->pdo->query("SHOW TABLES LIKE 'type_boisson'")->fetchAll(PDO::FETCH_ASSOC)
        ) > 0;

        $boissonSelect = 'b.nom_boisson';
        $boissonJoin = '';

        if (in_array('type_boisson', $boissonColumns, true)) {
            $boissonSelect .= ', b.type_boisson';
        } elseif ($typeBoissonTableExists && in_array('id_type', $boissonColumns, true)) {
            $boissonSelect .= ', tb.nom_type AS type_boisson';
            $boissonJoin = 'LEFT JOIN type_boisson tb ON b.id_type = tb.id_type';
        }

        $stmt = $this->pdo->prepare("
            SELECT
                d.*,
                p.nom_plat,
                p.prix_unitaire AS prix_plat,
                {$boissonSelect}
            FROM contient d
            LEFT JOIN plat p ON d.id_plat = p.id_plat
            LEFT JOIN boisson b ON d.id_boisson = b.id_boisson
            {$boissonJoin}
            WHERE d.num_commande = ?
        ");
        $stmt->execute([$numCommande]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return list<array<string, mixed>> */
    public function fetchReportLines(string $monthYm, ?string $dayYmd = null): array
    {
        $baseSql = '
            SELECT f.*, c.num_commande, c.num_table, cl.nom_client, cl.prenom_client
            FROM facture f
            JOIN commande c ON f.num_commande = c.num_commande
            LEFT JOIN client cl ON c.id_client = cl.id_client
        ';

        if ($dayYmd !== null && $dayYmd !== '') {
            $stmt = $this->pdo->prepare($baseSql . ' WHERE DATE(f.date_facture) = ? ORDER BY f.date_facture ASC');
            $stmt->execute([$dayYmd]);
        } else {
            $stmt = $this->pdo->prepare($baseSql . " WHERE DATE_FORMAT(f.date_facture, '%Y-%m') = ? ORDER BY f.date_facture ASC");
            $stmt->execute([$monthYm]);
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return list<string> */
    private function columnNames(string $table): array
    {
        return array_column(
            $this->pdo->query("SHOW COLUMNS FROM {$table}")->fetchAll(PDO::FETCH_ASSOC),
            'Field'
        );
    }
}
