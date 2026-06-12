<?php

/**
 * Pont procédural → App\Service\EmployePasswordService (POO).
 */

require_once __DIR__ . '/session_security.php';

function employe_ensure_password_column(PDO $pdo): void
{
    app()->employePasswordService()->ensureColumn();
}

function employe_upgrade_passwords(PDO $pdo): void
{
    app()->employePasswordService()->upgradePlaintextPasswords();
}

function employe_password_is_valid(string $password): bool
{
    return app()->passwordHasher()->isValidLength($password);
}
