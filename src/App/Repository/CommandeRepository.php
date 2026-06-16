<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Application;
use App\Model\CommandeLine;
use App\Model\CommandeStatut;
use PDO;

final class CommandeRepository extends BaseRepository
{
    private const KITCHEN_SELECT = "
        SELECT
            c.num_commande,
            c.date_commande,
            c.montant_total,
            c.statut,
            c.num_table,
            c.instructions_speciales,
            cl.nom_client,
            cl.prenom_client,
            cl.telephone_client,
            COUNT(d.id_detail) AS nombre_items,
            GROUP_CONCAT(
                CONCAT(COALESCE(p.nom_plat, b.nom_boisson), ' (x', d.quantite, ')')
                SEPARATOR ', '
            ) AS details_plats
        FROM commande c
        LEFT JOIN client cl ON c.id_client = cl.id_client
        LEFT JOIN contient d ON c.num_commande = d.num_commande
        LEFT JOIN plat p ON d.id_plat = p.id_plat
        LEFT JOIN boisson b ON d.id_boisson = b.id_boisson
    ";

    private const KITCHEN_GROUP = "
        GROUP BY c.num_commande, c.date_commande, c.montant_total, c.statut, c.num_table, c.instructions_speciales,
                 cl.nom_client, cl.prenom_client, cl.telephone_client
    ";

    /** @return list<array<string, mixed>> */
    public function findKitchenActive(): array
    {
        $sql = self::KITCHEN_SELECT . "
            WHERE c.statut IN ('en_attente', 'en_preparation')
        " . self::KITCHEN_GROUP . "
            ORDER BY c.statut DESC, c.date_commande ASC
        ";

        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return list<array<string, mixed>> */
    public function findKitchenReady(int $limit = 10): array
    {
        $sql = self::KITCHEN_SELECT . "
            WHERE c.statut = 'prete'
        " . self::KITCHEN_GROUP . "
            ORDER BY c.date_commande DESC
            LIMIT " . (int) $limit;

        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return list<array<string, mixed>> */
    public function findForCuisine(string $filtre, int $limit = 80): array
    {
        $where = match ($filtre) {
            'prete' => "c.statut = 'prete'",
            'toutes' => "c.statut NOT IN ('annulee')",
            default => "c.statut IN ('en_attente', 'en_preparation')",
        };

        $sql = "
            SELECT c.num_commande, c.date_commande, c.montant_total, c.statut, c.num_table, c.instructions_speciales,
                   cl.nom_client, cl.prenom_client, cl.telephone_client
            FROM commande c
            LEFT JOIN client cl ON c.id_client = cl.id_client
            WHERE {$where}
            ORDER BY c.date_commande DESC
            LIMIT " . (int) $limit;

        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return list<array<string, mixed>> */
    public function findRecentForCuisine(int $limit = 20): array
    {
        $sql = "
            SELECT c.num_commande, c.date_commande, c.montant_total, c.statut, c.num_table, c.instructions_speciales,
                   cl.nom_client, cl.prenom_client, cl.telephone_client
            FROM commande c
            LEFT JOIN client cl ON c.id_client = cl.id_client
            WHERE c.statut NOT IN ('annulee')
            ORDER BY c.date_commande DESC
            LIMIT " . (int) $limit;

        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function findForAdmin(?string $statutFilter, ?string $query, int $limit = 200): array
    {
        $sql = "
            SELECT c.*, cl.nom_client, cl.prenom_client, cl.email_client
            FROM commande c
            LEFT JOIN client cl ON c.id_client = cl.id_client
            WHERE 1=1
        ";
        $params = [];

        if ($statutFilter !== null && $statutFilter !== '' && CommandeStatut::isValid($statutFilter)) {
            $sql .= ' AND c.statut = ?';
            $params[] = $statutFilter;
        }

        if ($query !== null && $query !== '') {
            $sql .= ' AND (c.num_commande LIKE ? OR c.num_table LIKE ? OR CONCAT(cl.prenom_client, " ", cl.nom_client) LIKE ?)';
            $pattern = '%' . $query . '%';
            $params[] = $pattern;
            $params[] = $pattern;
            $params[] = $pattern;
        }

        $sql .= ' ORDER BY c.date_commande DESC LIMIT ' . (int) $limit;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return list<CommandeLine> */
    public function fetchLines(int $numCommande): array
    {
        Application::getInstance()->schemaUpgrade()->run();

        $stmt = $this->pdo->prepare("
            SELECT
                d.quantite,
                d.prix,
                d.sous_total,
                d.sauces,
                d.personnalisation_boisson,
                p.nom_plat,
                b.nom_boisson
            FROM contient d
            LEFT JOIN plat p ON d.id_plat = p.id_plat
            LEFT JOIN boisson b ON d.id_boisson = b.id_boisson
            WHERE d.num_commande = ?
            ORDER BY d.id_detail
        ");
        $stmt->execute([$numCommande]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(static fn(array $row): CommandeLine => CommandeLine::fromRow($row), $rows);
    }

    public function countByStatut(string $statut): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM commande WHERE statut = ?');
        $stmt->execute([$statut]);

        return (int) $stmt->fetchColumn();
    }

    public function updateStatut(int $numCommande, string $statut): void
    {
        $stmt = $this->pdo->prepare('UPDATE commande SET statut = ? WHERE num_commande = ?');
        $stmt->execute([$statut, $numCommande]);
    }

    public function markDelivered(int $numCommande): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE commande SET statut = 'livree' WHERE num_commande = ? AND statut = 'prete'"
        );
        $stmt->execute([$numCommande]);
    }

    /** @return list<array<string, mixed>> */
    public function findAwaitingPayment(): array
    {
        $modeSql = $this->hasColumn('commande', 'mode_paiement_souhaite')
            ? 'c.mode_paiement_souhaite,'
            : '';

        $sql = "
            SELECT c.num_commande, c.date_commande, c.montant_total, c.statut, c.num_table,
                   {$modeSql}
                   cl.nom_client, cl.prenom_client, cl.telephone_client
            FROM commande c
            LEFT JOIN client cl ON c.id_client = cl.id_client
            WHERE c.statut = 'livree'
              AND NOT EXISTS (SELECT 1 FROM facture f WHERE f.num_commande = c.num_commande)
            ORDER BY c.date_commande ASC
        ";

        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return list<array<string, mixed>> */
    public function findAwaitingPaymentDetailed(): array
    {
        $modeSql = $this->hasColumn('commande', 'mode_paiement_souhaite')
            ? 'c.mode_paiement_souhaite,'
            : '';

        $sql = "
            SELECT
                c.num_commande,
                c.date_commande,
                c.montant_total,
                {$modeSql}
                c.num_table,
                cl.nom_client,
                cl.prenom_client,
                cl.email_client,
                cl.telephone_client,
                (SELECT COUNT(*) FROM contient d WHERE d.num_commande = c.num_commande) AS nombre_items
            FROM commande c
            LEFT JOIN client cl ON c.id_client = cl.id_client
            WHERE c.statut = 'livree'
              AND NOT EXISTS (SELECT 1 FROM facture f WHERE f.num_commande = c.num_commande)
            ORDER BY c.date_commande ASC
        ";

        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return list<array<string, mixed>> */
    public function findRecentlyPaidWithFacture(int $limit = 80): array
    {
        $modeSql = $this->hasColumn('commande', 'mode_paiement_souhaite')
            ? 'c.mode_paiement_souhaite,'
            : '';

        $sql = "
            SELECT c.num_commande, c.date_commande, c.montant_total, c.statut, c.num_table,
                   {$modeSql}
                   cl.nom_client, cl.prenom_client, cl.telephone_client,
                   f.num_facture, f.total_paye, f.mode_paiement, f.date_facture AS date_paiement
            FROM facture f
            JOIN commande c ON f.num_commande = c.num_commande
            LEFT JOIN client cl ON c.id_client = cl.id_client
            ORDER BY f.date_facture DESC
            LIMIT " . (int) $limit;

        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return array<string, mixed>|null */
    public function findPaymentDetails(int $numCommande): ?array
    {
        $modeSql = $this->hasColumn('commande', 'mode_paiement_souhaite')
            ? 'c.mode_paiement_souhaite,'
            : '';

        $stmt = $this->pdo->prepare("
            SELECT
                c.num_commande,
                c.date_commande,
                c.montant_total,
                c.statut,
                {$modeSql}
                c.id_client,
                c.num_table,
                c.num_table AS table_num,
                cl.nom_client,
                cl.prenom_client,
                cl.telephone_client,
                cl.email_client,
                (
                    SELECT GROUP_CONCAT(
                        CONCAT(
                            COALESCE(p.nom_plat, b.nom_boisson),
                            ' (x', d.quantite, ') - ',
                            d.prix, ' FC = ',
                            d.sous_total, ' FC'
                        ) SEPARATOR '||'
                    )
                    FROM contient d
                    LEFT JOIN plat p ON d.id_plat = p.id_plat
                    LEFT JOIN boisson b ON d.id_boisson = b.id_boisson
                    WHERE d.num_commande = c.num_commande
                ) AS details_items
            FROM commande c
            LEFT JOIN client cl ON c.id_client = cl.id_client
            WHERE c.num_commande = ?
              AND c.statut = 'livree'
              AND NOT EXISTS (SELECT 1 FROM facture f WHERE f.num_commande = c.num_commande)
        ");
        $stmt->execute([$numCommande]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /** Détails commande après encaissement (lecture seule, facture déjà émise). */
    /** @return array<string, mixed>|null */
    public function findReceiptDetails(int $numCommande): ?array
    {
        $modeSql = $this->hasColumn('commande', 'mode_paiement_souhaite')
            ? 'c.mode_paiement_souhaite,'
            : '';

        $stmt = $this->pdo->prepare("
            SELECT
                c.num_commande,
                c.date_commande,
                c.montant_total,
                c.statut,
                {$modeSql}
                c.id_client,
                c.num_table,
                c.num_table AS table_num,
                cl.nom_client,
                cl.prenom_client,
                cl.telephone_client,
                cl.email_client
            FROM commande c
            LEFT JOIN client cl ON c.id_client = cl.id_client
            WHERE c.num_commande = ?
        ");
        $stmt->execute([$numCommande]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    private function hasColumn(string $table, string $column): bool
    {
        static $cache = [];

        if (!isset($cache[$table])) {
            $cache[$table] = array_column(
                $this->pdo->query("SHOW COLUMNS FROM {$table}")->fetchAll(PDO::FETCH_ASSOC),
                'Field'
            );
        }

        return in_array($column, $cache[$table], true);
    }

    /** @return array<string, mixed>|null */
    public function findForStatusApi(int $numCommande): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT c.num_commande, c.statut, c.montant_total, c.date_commande, c.mode_paiement_souhaite,
                   c.num_table, cl.nom_client, cl.prenom_client
            FROM commande c
            LEFT JOIN table_restaurant t ON c.num_table = t.num_table
            LEFT JOIN client cl ON c.id_client = cl.id_client
            WHERE c.num_commande = ?
        ');
        $stmt->execute([$numCommande]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /** @return array<string, mixed>|null */
    public function findAccessRow(int $numCommande): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT num_commande, num_table, id_client FROM commande WHERE num_commande = ?'
        );
        $stmt->execute([$numCommande]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /** @return array<string, mixed>|null */
    public function findForTracking(int $numCommande): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT c.*, cl.nom_client, cl.prenom_client, cl.email_client, cl.telephone_client,
                   t.num_table, t.libelle AS table_libelle
            FROM commande c
            LEFT JOIN client cl ON c.id_client = cl.id_client
            LEFT JOIN table_restaurant t ON c.num_table = t.num_table
            WHERE c.num_commande = ?
        ");
        $stmt->execute([$numCommande]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /** @return list<array<string, mixed>> */
    public function findTrackingLines(int $numCommande): array
    {
        Application::getInstance()->schemaUpgrade()->run();

        $stmt = $this->pdo->prepare("
            SELECT COALESCE(p.nom_plat, b.nom_boisson, d.personnalisation_boisson) AS nom,
                   d.quantite, d.prix, d.sous_total, d.personnalisation_boisson
            FROM contient d
            LEFT JOIN plat p ON d.id_plat = p.id_plat
            LEFT JOIN boisson b ON d.id_boisson = b.id_boisson
            WHERE d.num_commande = ?
            ORDER BY d.id_detail
        ");
        $stmt->execute([$numCommande]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return array<string, mixed>|null */
    public function findFactureForCommande(int $numCommande): ?array
    {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM facture WHERE num_commande = ? LIMIT 1');
            $stmt->execute([$numCommande]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return $row ?: null;
        } catch (\PDOException) {
            return null;
        }
    }

    /** @return array<string, mixed>|null */
    public function findForClientPayment(int $numCommande): ?array
    {
        $factureColumns = array_column(
            $this->pdo->query('SHOW COLUMNS FROM facture')->fetchAll(PDO::FETCH_ASSOC),
            'Field'
        );
        $factureSelect = 'f.num_facture, f.total_paye, f.date_facture';
        if (in_array('mode_paiement', $factureColumns, true)) {
            $factureSelect .= ', f.mode_paiement';
        }

        $stmt = $this->pdo->prepare("
            SELECT
                c.*,
                cl.nom_client,
                cl.prenom_client,
                cl.email_client,
                cl.telephone_client,
                t.num_table,
                {$factureSelect}
            FROM commande c
            LEFT JOIN client cl ON c.id_client = cl.id_client
            LEFT JOIN table_restaurant t ON c.num_table = t.num_table
            LEFT JOIN facture f ON c.num_commande = f.num_commande
            WHERE c.num_commande = ?
        ");
        $stmt->execute([$numCommande]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        if (!array_key_exists('mode_paiement', $row)) {
            $row['mode_paiement'] = null;
        }

        return $row;
    }

    /** @return array<string, mixed>|null */
    public function findReadyForPayment(int $numCommande): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM commande WHERE num_commande = ? AND statut IN ('prete', 'livree')"
        );
        $stmt->execute([$numCommande]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /** @return array<string, mixed>|null */
    public function findPaymentConfirmation(int $numCommande): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT c.*, t.num_table, cl.nom_client, cl.prenom_client
            FROM commande c
            LEFT JOIN table_restaurant t ON c.num_table = t.num_table
            LEFT JOIN client cl ON c.id_client = cl.id_client
            WHERE c.num_commande = ?
        ");
        $stmt->execute([$numCommande]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }
}
