<?php

declare(strict_types=1);

namespace App\Http;

use App\Core\Application;
use RuntimeException;

/** Dispatch uniforme des points d'entrée (Phase 2). */
final class Kernel
{
    /** @var array<string, array<string, mixed>>|null */
    private static ?array $routes = null;

    /**
     * Résout une route à partir du script PHP courant et retourne les données vue.
     *
     * @return array<string, mixed>|null null si redirect/API (réponse déjà envoyée)
     */
    public static function forFile(string $entryFile): ?array
    {
        $key = self::routeKeyFromFile($entryFile);

        return self::dispatch($key);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function dispatch(string $routeKey): ?array
    {
        $route = self::route($routeKey);

        self::runRequires($route);
        self::runAuth($route['auth'] ?? null);
        self::runSetup($route);

        if (isset($route['redirect'])) {
            self::redirect((string) $route['redirect'], (bool) ($route['preserve_query'] ?? false));
        }

        if (isset($route['invoke'])) {
            $result = ($route['invoke'])();
            if ($result === null) {
                return null;
            }

            return is_array($result) ? $result : [];
        }

        $controller = $route['controller'] ?? null;
        if ($controller === null) {
            throw new RuntimeException("Route sans contrôleur : {$routeKey}");
        }

        $result = self::invokeController($controller, $route['args'] ?? []);

        if (($route['response'] ?? 'html') !== 'html') {
            return null;
        }

        if (!is_array($result)) {
            return null;
        }

        return $result;
    }

    /** @return array<string, mixed> */
    private static function route(string $routeKey): array
    {
        $routes = self::routes();
        if (!isset($routes[$routeKey])) {
            throw new RuntimeException("Route inconnue : {$routeKey}");
        }

        return $routes[$routeKey];
    }

    /** @return array<string, array<string, mixed>> */
    private static function routes(): array
    {
        if (self::$routes === null) {
            /** @var array<string, array<string, mixed>> $routes */
            $routes = require dirname(__DIR__, 3) . '/config/routes.php';
            self::$routes = $routes;
        }

        return self::$routes;
    }

    private static function routeKeyFromFile(string $entryFile): string
    {
        $root = realpath(dirname(__DIR__, 3)) ?: dirname(__DIR__, 3);
        $file = realpath($entryFile) ?: $entryFile;
        $file = str_replace('\\', '/', $file);
        $root = str_replace('\\', '/', $root);

        if (!str_starts_with($file, $root)) {
            throw new RuntimeException("Fichier hors projet : {$entryFile}");
        }

        return ltrim(substr($file, strlen($root)), '/');
    }

    /** @param array<string, mixed> $route */
    private static function runRequires(array $route): void
    {
        foreach ($route['requires'] ?? [] as $file) {
            require_once $file;
        }
    }

    private static function runAuth(?string $auth): void
    {
        match ($auth) {
            null, '' => null,
            'admin' => AdminPage::init(),
            'admin.auth' => AdminPage::requireAuth(),
            'staff:admin' => StaffPage::require(['admin']),
            'staff:cuisinier' => StaffPage::require(['cuisinier']),
            'staff:caissier' => StaffPage::require(['caissier']),
            'staff:manager' => StaffPage::require(['manager']),
            'client.session' => ClientPage::startSession(),
            default => throw new RuntimeException("Auth inconnue : {$auth}"),
        };
    }

    /** @param array<string, mixed> $route */
    private static function runSetup(array $route): void
    {
        foreach ($route['setup'] ?? [] as $step) {
            match ($step) {
                'schema' => Application::getInstance()->schemaUpgrade()->run(),
                default => throw new RuntimeException("Setup inconnu : {$step}"),
            };
        }
    }

    /** @param array{0:class-string,1:string}|callable $controller */
    /** @param list<string> $argKeys */
    private static function invokeController(array|callable $controller, array $argKeys): mixed
    {
        $args = self::resolveArgs($argKeys);

        if (is_callable($controller)) {
            return $controller(...$args);
        }

        [$class, $method] = $controller;
        $instance = new $class();

        return $instance->{$method}(...$args);
    }

    /** @param list<string> $keys */
    /** @return list<mixed> */
    private static function resolveArgs(array $keys): array
    {
        $args = [];
        foreach ($keys as $key) {
            $args[] = match ($key) {
                'get' => $_GET,
                'post' => $_POST,
                'files' => $_FILES,
                'session' => $_SESSION,
                'staff_user' => StaffPage::user(),
                'logout_redirect' => 'login.php?logout=1',
                'export_download' => false,
                'export_inline' => true,
                default => throw new RuntimeException("Argument inconnu : {$key}"),
            };
        }

        return $args;
    }

    private static function redirect(string $target, bool $preserveQuery): never
    {
        if ($preserveQuery && !empty($_SERVER['QUERY_STRING'])) {
            $sep = str_contains($target, '?') ? '&' : '?';
            $target .= $sep . $_SERVER['QUERY_STRING'];
        }
        header('Location: ' . $target, true, 302);
        exit;
    }
}
