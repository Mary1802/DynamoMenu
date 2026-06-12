<?php

/**
 * Pont procédural → App\Auth\ClientSessionService (POO).
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/session_security.php';

use App\Auth\ClientSessionService;

const CLIENT_SESSION_NAME = ClientSessionService::SESSION_NAME;
const CLIENT_SESSION_DEFAULT_LIFETIME = 14400;

function client_session_lifetime(): int
{
    return app()->config()->clientSessionLifetime();
}

function client_session_start(): void
{
    app()->clientSession()->start();
}

function client_session_enforce_timeout(): void
{
    app()->clientSession()->enforceTimeout();
}

function client_logout(): void
{
    app()->clientSession()->logout();
}

function client_verify_post_csrf(): void
{
    app()->clientSession()->verifyPostCsrf();
}
