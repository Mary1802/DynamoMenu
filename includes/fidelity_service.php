<?php

/** Pont procédural → services POO (fidélité). */
require_once __DIR__ . '/bootstrap.php';

use App\Service\FidelityService;

function fidelity_ensure(PDO $pdo): void
{
    app()->fidelityService()->ensureSchema();
}

function fidelity_niveau(int $points): string
{
    return FidelityService::niveauFromPoints($points);
}

function fidelity_niveau_label(string $niveau): string
{
    return FidelityService::niveauLabel($niveau);
}

function fidelity_get_client(PDO $pdo, int $idClient): ?array
{
    return app()->fidelityService()->getClient($idClient);
}

function fidelity_list_rewards(PDO $pdo, bool $activeOnly = true): array
{
    return app()->fidelityService()->listRewards($activeOnly);
}

function fidelity_compute_discount(array $reward, float $subtotal): float
{
    return app()->fidelityService()->computeDiscount($reward, $subtotal);
}

function fidelity_apply_reward(PDO $pdo, int $idClient, int $idRecompense, float $subtotal): array
{
    return app()->fidelityService()->applyReward($idClient, $idRecompense, $subtotal);
}

function fidelity_redeem_reward(PDO $pdo, int $idClient, int $idRecompense, int $numCommande, int $pointsUsed): void
{
    app()->fidelityService()->redeemReward($idClient, $idRecompense, $numCommande, $pointsUsed);
}

function fidelity_add_points(PDO $pdo, int $idClient, int $points, string $type, string $description, ?int $numCommande = null, ?int $idRecompense = null): void
{
    app()->fidelityService()->addPoints($idClient, $points, $type, $description, $numCommande, $idRecompense);
}

function fidelity_award_after_payment(PDO $pdo, int $numCommande): void
{
    app()->fidelityService()->awardAfterPayment($numCommande);
}

function fidelity_history(PDO $pdo, int $idClient, int $limit = 20): array
{
    return app()->fidelityService()->history($idClient, $limit);
}
