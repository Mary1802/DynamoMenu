<?php

/**
 * Pont procédural → App\Auth\StaffAuthService (POO).
 */

require_once __DIR__ . '/session_security.php';

use App\Auth\StaffAuthService;

const STAFF_SESSION_KEY = StaffAuthService::SESSION_KEY;
const STAFF_SESSION_NAME = StaffAuthService::SESSION_NAME;

function staff_session_lifetime(): int
{
    return app()->config()->staffSessionLifetime();
}

function staff_session_cookie_path(): string
{
    return app()->config()->cookiePath();
}

function staff_session_start(): void
{
    app()->staffAuth()->startSession();
}

/** @param array<string, mixed> $employe */
function staff_login(array $employe, string $role): void
{
    app()->staffAuth()->login($employe, $role);
}

function staff_logout(): void
{
    app()->staffAuth()->logout();
}

/** @return array{user_id:int,nom:string,email:string,role:string,login_at:int}|null */
function staff_user(): ?array
{
    return app()->staffAuth()->user();
}

/** @param list<string> $allowedRoles */
function staff_require(array $allowedRoles, string $loginRedirect = '../login.php'): array
{
    return app()->staffAuth()->require($allowedRoles, $loginRedirect);
}

function staff_dashboard_url(string $role): string
{
    return app()->staffAuth()->dashboardUrl($role);
}

function staff_role_label(string $role): string
{
    return app()->staffAuth()->roleLabel($role);
}
