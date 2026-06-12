<?php

/**
 * Pont procédural → classes App\Security (POO).
 */

require_once __DIR__ . '/bootstrap.php';

use App\Security\Csrf;

const CSRF_SESSION_KEY = Csrf::SESSION_KEY;

function password_is_hashed(string $stored): bool
{
    return app()->passwordHasher()->isHashed($stored);
}

function app_secret(): string
{
    return app()->config()->secret();
}

function session_is_https(): bool
{
    return (new \App\Security\SessionCookie(app()->config()))->isHttps();
}

function app_cookie_path(): string
{
    return app()->config()->cookiePath();
}

/** @return array{lifetime:int,path:string,httponly:bool,samesite:string,secure:bool} */
function session_cookie_params(): array
{
    return (new \App\Security\SessionCookie(app()->config()))->params();
}

function csrf_token(): string
{
    return app()->csrf()->token();
}

function csrf_rotate(): void
{
    app()->csrf()->rotate();
}

function csrf_verify(?string $token = null): bool
{
    return app()->csrf()->verify($token);
}

function csrf_verify_or_abort(): void
{
    app()->csrf()->verifyOrAbort();
}

function csrf_field(): void
{
    app()->csrf()->field();
}

function csrf_meta_tag(): void
{
    app()->csrf()->metaTag();
}

function password_verify_employe(string $plain, string $stored): bool
{
    return app()->passwordHasher()->verify($plain, $stored);
}

function password_hash_employe(string $plain): string
{
    return app()->passwordHasher()->hash($plain);
}

function password_employe_needs_rehash(string $stored): bool
{
    return app()->passwordHasher()->needsRehash($stored);
}

function client_order_grant_access(int $numCommande): void
{
    app()->orderAccess()->grant($numCommande);
}

function client_order_token(int $numCommande): string
{
    return app()->orderAccess()->token($numCommande);
}

function client_order_token_verify(int $numCommande, string $token): bool
{
    return app()->orderAccess()->verifyToken($numCommande, $token);
}

/** @param array<string, mixed> $commande */
function client_can_access_order(array $commande, ?string $token = null): bool
{
    return app()->orderAccess()->canAccess($commande, $token);
}

/** @param array<string, mixed> $commande */
function client_require_order_access(array $commande, ?string $token = null): void
{
    app()->orderAccess()->requireAccess($commande, $token);
}
