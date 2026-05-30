<?php

require_once __DIR__ . '/schema_upgrade.php';

function fidelity_ensure(PDO $pdo): void
{
    schema_upgrade($pdo);
}

function fidelity_niveau(int $points): string
{
    if ($points >= 150) {
        return 'or';
    }
    if ($points >= 50) {
        return 'argent';
    }

    return 'bronze';
}

function fidelity_niveau_label(string $niveau): string
{
    return match ($niveau) {
        'or' => 'Or',
        'argent' => 'Argent',
        default => 'Bronze',
    };
}

function fidelity_get_client(PDO $pdo, int $idClient): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM client WHERE id_client = ?');
    $stmt->execute([$idClient]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$client) {
        return null;
    }
    $points = (int) $client['points'];
    $niveau = fidelity_niveau($points);
    if (($client['niveau_fidelite'] ?? '') !== $niveau) {
        $pdo->prepare('UPDATE client SET niveau_fidelite = ? WHERE id_client = ?')->execute([$niveau, $idClient]);
        $client['niveau_fidelite'] = $niveau;
    }

    return $client;
}

function fidelity_list_rewards(PDO $pdo, bool $activeOnly = true): array
{
    $sql = 'SELECT * FROM recompense_fidelite';
    if ($activeOnly) {
        $sql .= ' WHERE actif = 1';
    }
    $sql .= ' ORDER BY points_requis ASC';

    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

function fidelity_compute_discount(array $reward, float $subtotal): float
{
    return match ($reward['type_recompense']) {
        'pourcentage' => round($subtotal * ((float) $reward['valeur'] / 100), 2),
        'montant_fixe' => min($subtotal, (float) $reward['valeur']),
        'cadeau' => 0.0,
        default => 0.0,
    };
}

function fidelity_apply_reward(PDO $pdo, int $idClient, int $idRecompense, float $subtotal): array
{
    fidelity_ensure($pdo);
    $stmt = $pdo->prepare('SELECT * FROM recompense_fidelite WHERE id_recompense = ? AND actif = 1');
    $stmt->execute([$idRecompense]);
    $reward = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$reward) {
        throw new InvalidArgumentException('Récompense introuvable.');
    }

    $client = fidelity_get_client($pdo, $idClient);
    if ((int) $client['points'] < (int) $reward['points_requis']) {
        throw new InvalidArgumentException('Points insuffisants pour cette récompense.');
    }

    $remise = fidelity_compute_discount($reward, $subtotal);

    return [
        'reward' => $reward,
        'remise' => $remise,
        'points_requis' => (int) $reward['points_requis'],
    ];
}

function fidelity_redeem_reward(PDO $pdo, int $idClient, int $idRecompense, int $numCommande, int $pointsUsed): void
{
    $pdo->prepare('UPDATE client SET points = GREATEST(0, points - ?) WHERE id_client = ?')->execute([$pointsUsed, $idClient]);
    $stmt = $pdo->prepare('INSERT INTO historique_points (id_client, points, type_operation, description, num_commande, id_recompense)
        VALUES (?, ?, \'echange\', ?, ?, ?)');
    $stmt->execute([$idClient, -$pointsUsed, 'Échange récompense fidélité', $numCommande, $idRecompense]);
    fidelity_get_client($pdo, $idClient);
}

function fidelity_add_points(PDO $pdo, int $idClient, int $points, string $type, string $description, ?int $numCommande = null, ?int $idRecompense = null): void
{
    if ($points === 0) {
        return;
    }
    $pdo->prepare('UPDATE client SET points = points + ? WHERE id_client = ?')->execute([$points, $idClient]);
    $stmt = $pdo->prepare('INSERT INTO historique_points (id_client, points, type_operation, description, num_commande, id_recompense)
        VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$idClient, $points, $type, $description, $numCommande, $idRecompense]);
    fidelity_get_client($pdo, $idClient);
}

/** Points gagnés : 1 point par euro payé (arrondi). */
function fidelity_award_after_payment(PDO $pdo, int $numCommande): void
{
    fidelity_ensure($pdo);
    $stmt = $pdo->prepare('
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
    fidelity_add_points($pdo, (int) $row['id_client'], $points, 'gain', 'Points suite au paiement', $numCommande);
    $pdo->prepare('UPDATE commande SET points_gagnes = ? WHERE num_commande = ?')->execute([$points, $numCommande]);

    require_once __DIR__ . '/notification_service.php';
    notification_create(
        $pdo,
        (int) $row['id_client'],
        $numCommande,
        'fidelite',
        'Points fidélité',
        "Vous avez gagné {$points} points. Merci pour votre visite !"
    );
}

function fidelity_history(PDO $pdo, int $idClient, int $limit = 20): array
{
    $stmt = $pdo->prepare('
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
