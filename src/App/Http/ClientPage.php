<?php

declare(strict_types=1);

namespace App\Http;

use App\Core\Application;
use App\View\Client\ClientFooterView;
use App\View\Client\ClientNavView;

final class ClientPage
{
    public static function startSession(): void
    {
        Application::getInstance()->clientSession()->start();
    }

    public static function enforceTimeout(): void
    {
        Application::getInstance()->clientSession()->enforceTimeout();
    }

    public static function verifyPostCsrf(): void
    {
        Application::getInstance()->clientSession()->verifyPostCsrf();
    }

    public static function logout(): void
    {
        Application::getInstance()->clientSession()->logout();
    }

    public static function csrfMetaTag(): void
    {
        Application::getInstance()->csrf()->metaTag();
    }

    public static function csrfField(): void
    {
        Application::getInstance()->csrf()->field();
    }

    /** @param 'index'|'menu'|'panier'|'' $active */
    public static function nav(string $active = ''): void
    {
        ClientNavView::nav($active);
    }

    /** @param array{num_table:int,label:string,code_table?:string|null} $tableCtx */
    public static function tableWelcome(array $tableCtx): void
    {
        ClientNavView::tableWelcome($tableCtx);
    }

    public static function tableError(string $message): void
    {
        ClientNavView::tableError($message);
    }

    /** @param array{num_table:int,label:string} $tableCtx */
    public static function tableStrip(array $tableCtx): void
    {
        ClientNavView::tableStrip($tableCtx);
    }

    public static function footer(): void
    {
        ClientFooterView::render();
    }

    public static function tableLink(string $path): string
    {
        return Application::getInstance()->tableContextService()->link($path);
    }

    public static function bootstrapTableContext(): void
    {
        Application::getInstance()->tableContextService()->bootstrap();
    }

    /** @return array{num_table:int,code_table:?string,label:string}|null */
    public static function tableSession(): ?array
    {
        return Application::getInstance()->tableContextService()->session();
    }

    public static function tableRequireOrRedirect(string $redirect = 'index.php'): void
    {
        Application::getInstance()->tableContextService()->requireOrRedirect($redirect);
    }
}
