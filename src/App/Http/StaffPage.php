<?php

declare(strict_types=1);

namespace App\Http;

use App\Core\Application;

final class StaffPage
{
    public static function startSession(): void
    {
        Application::getInstance()->staffAuth()->startSession();
    }

    /** @param array<string, mixed> $employe */
    public static function login(array $employe, string $role): void
    {
        Application::getInstance()->staffAuth()->login($employe, $role);
    }

    public static function logout(): void
    {
        Application::getInstance()->staffAuth()->logout();
    }

    /** @return array{user_id:int,nom:string,email:string,role:string,login_at:int}|null */
    public static function user(): ?array
    {
        return Application::getInstance()->staffAuth()->user();
    }

    /** @param list<string> $allowedRoles */
    /** @return array{user_id:int,nom:string,email:string,role:string,login_at:int} */
    public static function require(array $allowedRoles, string $loginRedirect = '../login.php'): array
    {
        return Application::getInstance()->staffAuth()->require($allowedRoles, $loginRedirect);
    }

    public static function dashboardUrl(string $role): string
    {
        return Application::getInstance()->staffAuth()->dashboardUrl($role);
    }

    public static function roleLabel(string $role): string
    {
        return Application::getInstance()->staffAuth()->roleLabel($role);
    }
}
