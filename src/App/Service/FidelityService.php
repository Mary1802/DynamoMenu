<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\Application;
use InvalidArgumentException;
use PDO;

final class FidelityService
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly ?SchemaUpgradeService $schema = null,
    ) {
    }

    public static function fromApp(?Application $app = null): self
    {
        $app ??= Application::getInstance();

        return new self($app->db(), $app->schemaUpgrade());
    }

    public function ensureSchema(): void
    {
        ($this->schema ?? SchemaUpgradeService::fromApp())->run();
    }

    public static function niveauFromPoints(int $points): string
    {
        if ($points >= 150) {
            return 'or';
        }
        if ($points >= 50) {
            return 'argent';
        }

        return 'bronze';
    }

    public static function niveauLabel(string $niveau): string
    {
        return match ($niveau) {
            'or' => 'Or',
            'argent' => 'Argent',
            default => 'Bronze',
        };
    }

    public function niveauLabelFor(?string $niveau, int $points): string
    {
        return self::niveauLabel($niveau ?? self::niveauFromPoints($points));
    }

    /** @return array<string, mixed>|null */
    public function getClient(int $idClient): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM client WHERE id_client = ?');
        $stmt->execute([$idClient]);
        $client = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$client) {
            return null;
        }

        $points = (int) $client['points'];
        $niveau = self::niveauFromPoints($points);
        if (($client['niveau_fidelite'] ?? '') !== $niveau) {
            $this->pdo->prepare('UPDATE client SET niveau_fidelite = ? WHERE id_client = ?')->execute([$niveau, $idClient]);
            $client['niveau_fidelite'] = $niveau;
        }

        return $client;
    }

    /** @return list<array<string, mixed>> */
    public function listRewards(bool $activeOnly = true): array
    {
        $sql = 'SELECT * FROM recompense_fidelite';
        if ($activeOnly) {
            $sql .= ' WHERE actif = 1';
        }
        $sql .= ' ORDER BY points_requis ASC';

        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @param array<string, mixed> $reward */
    public function computeDiscount(array $reward, float $subtotal): float
    {
        return match ($reward['type_recompense']) {
            'pourcentage' => round($subtotal * ((float) $reward['valeur'] / 100), 2),
            'montant_fixe' => min($subtotal, (float) $reward['valeur']),
            'cadeau' => 0.0,
            default => 0.0,
        };
    }

    /** @return array{reward:array<string,mixed>,remise:float,points_requis:int} */
    public function applyReward(int $idClient, int $idRecompense, float $subtotal): array
    {
        $this->ensureSchema();
        $stmt = $this->pdo->prepare('SELECT * FROM recompense_fidelite WHERE id_recompense = ? AND actif = 1');
        $stmt->execute([$idRecompense]);
        $reward = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$reward) {
            throw new InvalidArgumentException('Récompense introuvable.');
        }

        $client = $this->getClient($idClient);
        if ($client === null || (int) $client['points'] < (int) $reward['points_requis']) {
            throw new InvalidArgumentException('Points insuffisants pour cette récompense.');
        }

        return [
            'reward' => $reward,
            'remise' => $this->computeDiscount($reward, $subtotal),
            'points_requis' => (int) $reward['points_requis'],
        ];
    }

    public function redeemReward(int $idClient, int $idRecompense, int $numCommande, int $pointsUsed): void
    {
        $this->pdo->prepare('UPDATE client SET points = GREATEST(0, points - ?) WHERE id_client = ?')->execute([$pointsUsed, $idClient]);
        $stmt = $this->pdo->prepare(
            'INSERT INTO historique_points (id_client, points, type_operation, description, num_commande, id_recompense)
             VALUES (?, ?, \'echange\', ?, ?, ?)'
        );
        $stmt->execute([$idClient, -$pointsUsed, 'Échange récompense fidélité', $numCommande, $idRecompense]);
        $this->getClient($idClient);
    }

    public function addPoints(
        int $idClient,
        int $points,
        string $type,
        string $description,
        ?int $numCommande = null,
        ?int $idRecompense = null
    ): void {
        if ($points === 0) {
            return;
        }
        $this->pdo->prepare('UPDATE client SET points = points + ? WHERE id_client = ?')->execute([$points, $idClient]);
        $stmt = $this->pdo->prepare(
            'INSERT INTO historique_points (id_client, points, type_operation, description, num_commande, id_recompense)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$idClient, $points, $type, $description, $numCommande, $idRecompense]);
        $this->getClient($idClient);
    }

    public function adjustPoints(int $idClient, int $delta, string $note): void
    {
        $this->ensureSchema();
        $this->addPoints($idClient, $delta, 'ajustement', $note);
    }

    public function awardAfterPayment(int $numCommande): void
    {
        $this->ensureSchema();
        $stmt = $this->pdo->prepare('
            SELECT c.id_client, f.total_paye, c.points_gagnes
            FROM commande c
            JOIN facture f ON f.num_commande = c.num_commande
            WHERE c.num_commande = ?
        ');
        $stmt->execute([$numCommande]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || empty($row['id_client']) || (int) $row['points_gagnes'] > 0) {
            return;
        }

        $points = max(1, (int) floor((float) $row['total_paye']));
        $this->addPoints((int) $row['id_client'], $points, 'gain', 'Points suite au paiement', $numCommande);
        $this->pdo->prepare('UPDATE commande SET points_gagnes = ? WHERE num_commande = ?')->execute([$points, $numCommande]);

        Application::getInstance()->notificationService()->create(
            (int) $row['id_client'],
            $numCommande,
            'fidelite',
            'Points fidélité',
            "Vous avez gagné {$points} points. Merci pour votre visite !"
        );
    }

    /** @return list<array<string, mixed>> */
    public function history(int $idClient, int $limit = 20): array
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM historique_points
            WHERE id_client = ?
            ORDER BY date_operation DESC
            LIMIT ?
        ');
        $stmt->bindValue(1, $idClient, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return array<string, mixed>
     */
    public function lookupByEmail(string $email): array
    {
        $this->ensureSchema();

        $stmt = $this->pdo->prepare(
            'SELECT id_client, points, niveau_fidelite, nom_client, prenom_client FROM client WHERE email_client = ?'
        );
        $stmt->execute([$email]);
        $client = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$client) {
            return [
                'exists' => false,
                'points' => 0,
                'rewards' => $this->listRewards(),
            ];
        }

        $client = $this->getClient((int) $client['id_client']);
        $rewards = $this->listRewards();
        $available = array_values(array_filter(
            $rewards,
            static fn(array $r): bool => (int) $client['points'] >= (int) $r['points_requis']
        ));

        return [
            'exists' => true,
            'id_client' => (int) $client['id_client'],
            'points' => (int) $client['points'],
            'niveau' => $client['niveau_fidelite'],
            'niveau_label' => self::niveauLabel((string) $client['niveau_fidelite']),
            'rewards' => $rewards,
            'rewards_available' => $available,
        ];
    }

    /** @return array<string, string> */
    public static function rewardTypeLabels(): array
    {
        return [
            'pourcentage' => '% réduction',
            'montant_fixe' => 'Montant fixe (FC)',
            'cadeau' => 'Cadeau',
        ];
    }

    public function createReward(
        string $libelle,
        string $description,
        int $pointsRequis,
        string $type,
        float $valeur
    ): void {
        $stmt = $this->pdo->prepare(
            'INSERT INTO recompense_fidelite (libelle, description, points_requis, type_recompense, valeur, actif) VALUES (?, ?, ?, ?, ?, 1)'
        );
        $stmt->execute([$libelle, $description, $pointsRequis, $type, $valeur]);
    }

    public function updateReward(
        int $id,
        string $libelle,
        string $description,
        int $pointsRequis,
        string $type,
        float $valeur,
        bool $actif
    ): void {
        $stmt = $this->pdo->prepare(
            'UPDATE recompense_fidelite SET libelle = ?, description = ?, points_requis = ?, type_recompense = ?, valeur = ?, actif = ? WHERE id_recompense = ?'
        );
        $stmt->execute([$libelle, $description, $pointsRequis, $type, $valeur, $actif ? 1 : 0, $id]);
    }

    public function deleteReward(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM recompense_fidelite WHERE id_recompense = ?');
        $stmt->execute([$id]);
    }
}
