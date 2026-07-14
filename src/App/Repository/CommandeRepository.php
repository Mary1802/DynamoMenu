<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Application;
use App\Model\CommandeLine;
use App\Model\CommandeStatut;
use PDO;

final class CommandeRepository extends BaseRepository
{
    private const BOISSON_PREP_MINUTES = 2;
    private const DEFAULT_PREP_MINUTES = 15;
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
                CONCAT(
                    COALESCE(NULLIF(p.nom_plat, ''), NULLIF(b.nom_boisson, ''), NULLIF(d.personnalisation_boisson, ''), 'Article'),
                    ' (x', d.quantite, ')'
                )
                SEPARATOR ', '
            ) AS details_plats
        FROM commande c
        LEFT JOIN client cl ON c.id_client = cl.id_client
        LEFT JOIN contient d ON c.num_commande = d.num_commande
        LEFT JOIN plat p ON d.id_plat = p.id_plat AND d.id_plat IS NOT NULL AND d.id_plat > 0
        LEFT JOIN boisson b ON d.id_boisson = b.id_boisson AND d.id_boisson IS NOT NULL AND d.id_boisson > 0
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
    public function findReadyForManager(int $limit = 50): array
    {
        $sql = self::KITCHEN_SELECT . "
            WHERE c.statut = 'prete'
        " . self::KITCHEN_GROUP . "
            ORDER BY c.date_commande ASC
            LIMIT " . (int) $limit;

        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function findForManager(?string $filtre, ?string $query, ?string $dateYmd, int $limit = 100): array
    {
        $where = ["c.statut NOT IN ('annulee')"];
        $params = [];

        $filtre = $filtre ?? 'toutes';
        if ($filtre === 'a_livrer') {
            $where[] = "c.statut = 'prete'";
        } elseif ($filtre === 'livrees') {
            $where[] = "c.statut = 'livree'";
        } elseif ($filtre === 'toutes' || $filtre === 'service') {
            $where[] = "c.statut IN ('prete', 'livree')";
        }

        if ($query !== null && $query !== '') {
            $where[] = '(
                CAST(c.num_commande AS CHAR) LIKE ?
                OR c.num_table LIKE ?
                OR cl.nom_client LIKE ?
                OR cl.prenom_client LIKE ?
                OR cl.telephone_client LIKE ?
                OR CONCAT(cl.prenom_client, " ", cl.nom_client) LIKE ?
                OR c.instructions_speciales LIKE ?
            )';
            $pattern = '%' . $query . '%';
            foreach (range(1, 7) as $_) {
                $params[] = $pattern;
            }
        }

        if ($dateYmd !== null && $dateYmd !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateYmd)) {
            $where[] = 'DATE(c.date_commande) = ?';
            $params[] = $dateYmd;
        }

        $sql = self::KITCHEN_SELECT . '
            WHERE ' . implode(' AND ', $where) . self::KITCHEN_GROUP . '
            ORDER BY c.date_commande DESC
            LIMIT ' . (int) $limit;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return list<array<string, mixed>> */
    public function findRecentDeliveredForManager(int $limit = 15): array
    {
        $sql = self::KITCHEN_SELECT . "
            WHERE c.statut = 'livree'
        " . self::KITCHEN_GROUP . "
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
                d.id_plat,
                d.id_boisson,
                d.quantite,
                d.prix,
                d.sous_total,
                d.sauces,
                d.personnalisation_boisson,
                p.nom_plat,
                b.nom_boisson
            FROM contient d
            LEFT JOIN plat p ON d.id_plat = p.id_plat AND d.id_plat IS NOT NULL AND d.id_plat > 0
            LEFT JOIN boisson b ON d.id_boisson = b.id_boisson AND d.id_boisson IS NOT NULL AND d.id_boisson > 0
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

    public function findStatut(int $numCommande): ?string
    {
        $stmt = $this->pdo->prepare('SELECT statut FROM commande WHERE num_commande = ?');
        $stmt->execute([$numCommande]);
        $statut = $stmt->fetchColumn();

        return $statut === false ? null : (string) $statut;
    }

    public function updateStatut(int $numCommande, string $statut): void
    {
        $stmt = $this->pdo->prepare('UPDATE commande SET statut = ? WHERE num_commande = ?');
        $stmt->execute([$statut, $numCommande]);
    }

    public function startPreparationTracking(int $numCommande): void
    {
        Application::getInstance()->schemaUpgrade()->run();

        $seconds = $this->calculateEstimatedPrepSeconds($numCommande);

        $stmt = $this->pdo->prepare(
            'SELECT statut, date_debut_preparation, temps_preparation_estime_sec FROM commande WHERE num_commande = ?'
        );
        $stmt->execute([$numCommande]);
        $current = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $alreadyRunning = ($current['statut'] ?? '') === 'en_preparation'
            && trim((string) ($current['date_debut_preparation'] ?? '')) !== ''
            && (int) ($current['temps_preparation_estime_sec'] ?? 0) > 0;

        if ($alreadyRunning) {
            // Ne pas prolonger un timer déjà lancé.
            $this->pdo->prepare(
                "UPDATE commande SET statut = 'en_preparation' WHERE num_commande = ?"
            )->execute([$numCommande]);

            return;
        }

        $startedAt = time();
        $stmt = $this->pdo->prepare("
            UPDATE commande
            SET statut = 'en_preparation',
                date_debut_preparation = FROM_UNIXTIME(?),
                temps_preparation_estime_sec = ?
            WHERE num_commande = ?
        ");
        $stmt->execute([$startedAt, $seconds, $numCommande]);
    }

    public function calculateEstimatedPrepSeconds(int $numCommande): int
    {
        return $this->calculateEstimatedPrepMinutes($numCommande) * 60;
    }

    /**
     * Temps estimé = somme (temps_préparation × quantité) sur les plats.
     * Ex. 3× frites à 15 min → 45 min. Les boissons ne prolongent pas le countdown.
     */
    public function calculateEstimatedPrepMinutes(int $numCommande): int
    {
        $rows = $this->fetchContientRowsForTracking($numCommande);
        if ($rows === []) {
            return self::DEFAULT_PREP_MINUTES;
        }

        $total = 0;
        foreach ($rows as $row) {
            $idBoisson = (int) ($row['id_boisson'] ?? 0);
            if ($idBoisson > 0 || !empty($row['is_boisson_line'])) {
                continue;
            }

            $qty = max(1, (int) ($row['quantite'] ?? 1));
            $total += $this->resolveLinePrepMinutes($row) * $qty;
        }

        return $total > 0 ? $total : self::DEFAULT_PREP_MINUTES;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function resolveLinePrepMinutes(array $row): int
    {
        $name = $this->extractProductName((string) ($row['personnalisation_boisson'] ?? ''));
        if ($name === '') {
            $name = $this->extractProductName((string) ($row['nom'] ?? ''));
        }

        $idBoisson = (int) ($row['id_boisson'] ?? 0);
        if ($idBoisson > 0 || !empty($row['is_boisson_line'])) {
            return self::BOISSON_PREP_MINUTES;
        }

        if ($name !== '' && $this->boissonExistsByName($name)) {
            return self::BOISSON_PREP_MINUTES;
        }

        $idPlat = (int) ($row['id_plat'] ?? 0);
        if ($idPlat > 0) {
            return $this->platPrepMinutesById($idPlat);
        }

        if ($name !== '') {
            $platMinutes = $this->platPrepMinutesByName($name);
            if ($platMinutes !== null) {
                return $platMinutes;
            }
        }

        return self::DEFAULT_PREP_MINUTES;
    }

    private function extractProductName(string $label): string
    {
        $label = trim($label);
        if ($label === '') {
            return '';
        }

        $parts = explode(' — ', $label, 2);

        return trim($parts[0]);
    }

    private function parseDbDateTime(string $value): ?int
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        // Interpréter via MySQL pour rester aligné avec FROM_UNIXTIME / NOW() session.
        try {
            $stmt = $this->pdo->prepare('SELECT UNIX_TIMESTAMP(?)');
            $stmt->execute([$value]);
            $ts = $stmt->fetchColumn();
            if ($ts !== false && (int) $ts > 0) {
                return (int) $ts;
            }
        } catch (\PDOException) {
            // fallback ci-dessous
        }

        $dt = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $value);
        if ($dt instanceof \DateTimeImmutable) {
            return $dt->getTimestamp();
        }

        $ts = strtotime($value);

        return $ts !== false ? $ts : null;
    }

    private function platPrepMinutesById(int $idPlat): int
    {
        static $cache = [];
        if (isset($cache[$idPlat])) {
            return $cache[$idPlat];
        }

        Application::getInstance()->schemaUpgrade()->run();
        $hasPrepCol = $this->hasColumn('plat', 'temps_preparation_min');
        $col = $hasPrepCol ? 'temps_preparation_min' : null;

        if ($col === null) {
            return $cache[$idPlat] = 15;
        }

        $stmt = $this->pdo->prepare("SELECT {$col} FROM plat WHERE id_plat = ? LIMIT 1");
        $stmt->execute([$idPlat]);
        $value = (int) $stmt->fetchColumn();

        return $cache[$idPlat] = ($value > 0 ? $value : 15);
    }

    private function platPrepMinutesByName(string $name): ?int
    {
        static $cache = [];
        if (array_key_exists($name, $cache)) {
            return $cache[$name];
        }

        Application::getInstance()->schemaUpgrade()->run();
        if (!$this->hasColumn('plat', 'temps_preparation_min')) {
            return $cache[$name] = 15;
        }

        $stmt = $this->pdo->prepare('SELECT temps_preparation_min FROM plat WHERE nom_plat = ? LIMIT 1');
        $stmt->execute([$name]);
        $value = $stmt->fetchColumn();
        if ($value === false) {
            return $cache[$name] = null;
        }

        $minutes = (int) $value;

        return $cache[$name] = ($minutes > 0 ? $minutes : 15);
    }

    private function boissonExistsByName(string $name): bool
    {
        $name = trim($name);
        if ($name === '') {
            return false;
        }

        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM boisson WHERE LOWER(TRIM(nom_boisson)) = LOWER(?) LIMIT 1'
        );
        $stmt->execute([$name]);

        return (bool) $stmt->fetchColumn();
    }

    /** @return list<array<string, mixed>> */
    private function fetchContientRowsForTracking(int $numCommande): array
    {
        $stmt = $this->pdo->prepare("
            SELECT d.id_plat, d.id_boisson, d.quantite, d.prix, d.sous_total, d.personnalisation_boisson,
                   COALESCE(p.nom_plat, b.nom_boisson, d.personnalisation_boisson) AS nom,
                   (b.id_boisson IS NOT NULL) AS is_boisson_line
            FROM contient d
            LEFT JOIN plat p ON d.id_plat = p.id_plat
            LEFT JOIN boisson b ON d.id_boisson = b.id_boisson
            WHERE d.num_commande = ?
            ORDER BY d.id_detail
        ");
        $stmt->execute([$numCommande]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param array<string, mixed> $row
     * @return array{
     *   countdown_active: bool,
     *   prep_started_at: string|null,
     *   prep_total_seconds: int,
     *   prep_remaining_seconds: int,
     *   prep_end_unix: int|null,
     *   server_unix: int,
     *   prep_finished: bool
     * }
     */
    public function buildCountdownState(array $row): array
    {
        $statut = (string) ($row['statut'] ?? '');
        $startedAt = isset($row['date_debut_preparation']) ? (string) $row['date_debut_preparation'] : '';
        $isPreparing = $statut === 'en_preparation';
        $isReady = in_array($statut, ['prete', 'livree'], true);
        $now = time();

        $numCommande = (int) ($row['num_commande'] ?? 0);
        $storedSec = (int) ($row['temps_preparation_estime_sec'] ?? 0);
        // Une fois démarré, on fige la durée stockée (évite de prolonger à chaque refresh).
        if ($isPreparing && $storedSec > 0) {
            $totalSec = $storedSec;
        } else {
            $estimatedSec = $numCommande > 0 ? $this->calculateEstimatedPrepSeconds($numCommande) : 0;
            $totalSec = $estimatedSec > 0 ? $estimatedSec : $storedSec;
        }

        $active = $isPreparing && $startedAt !== '' && $totalSec > 0;
        $remaining = 0;
        $endTs = null;
        if ($active) {
            $startTs = $this->parseDbDateTime($startedAt);
            if ($startTs !== null) {
                $endTs = $startTs + $totalSec;
                $remaining = max(0, $endTs - $now);
            }
        }

        return [
            'countdown_active' => $active && !$isReady && $endTs !== null,
            'prep_started_at' => $startedAt !== '' ? $startedAt : null,
            'prep_total_seconds' => $totalSec,
            'prep_total_minutes' => $totalSec > 0 ? (int) round($totalSec / 60) : 0,
            'prep_remaining_seconds' => $remaining,
            'prep_end_unix' => $endTs,
            'server_unix' => $now,
            'prep_finished' => $isReady || ($active && $remaining <= 0),
        ];
    }

    /** @return list<array<string, mixed>> */
    public function findClientOrderSummaries(array $nums): array
    {
        $nums = array_values(array_filter(array_map('intval', $nums), static fn (int $n): bool => $n > 0));
        if ($nums === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($nums), '?'));
        $stmt = $this->pdo->prepare("
            SELECT c.num_commande, c.statut, c.date_commande, c.montant_total, c.num_table,
                   c.date_debut_preparation, c.temps_preparation_estime_sec
            FROM commande c
            WHERE c.num_commande IN ({$placeholders})
              AND NOT EXISTS (
                  SELECT 1 FROM facture f WHERE f.num_commande = c.num_commande
              )
            ORDER BY c.date_commande DESC
        ");
        $stmt->execute($nums);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Commandes non encore encaissées (sans facture).
     *
     * @param list<int> $nums
     * @return list<int>
     */
    public function filterUnpaidOrderIds(array $nums): array
    {
        $nums = array_values(array_filter(array_map('intval', $nums), static fn (int $n): bool => $n > 0));
        if ($nums === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($nums), '?'));
        $stmt = $this->pdo->prepare("
            SELECT c.num_commande
            FROM commande c
            WHERE c.num_commande IN ({$placeholders})
              AND NOT EXISTS (
                  SELECT 1 FROM facture f WHERE f.num_commande = c.num_commande
              )
        ");
        $stmt->execute($nums);

        /** @var list<int> */
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public function isOrderPaid(int $numCommande): bool
    {
        if ($numCommande <= 0) {
            return false;
        }

        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM facture WHERE num_commande = ? LIMIT 1'
        );
        $stmt->execute([$numCommande]);

        return (bool) $stmt->fetchColumn();
    }

    /**
     * Commandes récentes pour une table (historique client sur la tablette).
     *
     * @return list<int>
     */
    public function findRecentNumCommandesForTable(int $numTable, ?int $sinceUnix = null, int $limit = 30): array
    {
        if ($numTable <= 0) {
            return [];
        }

        if ($sinceUnix === null || $sinceUnix <= 0) {
            $sinceUnix = time() - 86400;
        }

        $stmt = $this->pdo->prepare('
            SELECT c.num_commande
            FROM commande c
            WHERE c.num_table = ?
              AND c.date_commande >= FROM_UNIXTIME(?)
              AND NOT EXISTS (
                  SELECT 1 FROM facture f WHERE f.num_commande = c.num_commande
              )
            ORDER BY c.date_commande DESC
            LIMIT ' . (int) $limit . '
        ');
        $stmt->execute([$numTable, $sinceUnix]);

        /** @var list<int> */
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
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
                   c.num_table, c.date_debut_preparation, c.temps_preparation_estime_sec,
                   cl.nom_client, cl.prenom_client
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

        $rows = $this->fetchContientRowsForTracking($numCommande);
        foreach ($rows as &$row) {
            $row['temps_preparation_min'] = $this->resolveLinePrepMinutes($row);
        }
        unset($row);

        return $rows;
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
