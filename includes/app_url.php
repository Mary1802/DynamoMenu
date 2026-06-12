<?php

require_once __DIR__ . '/bootstrap.php';

/** @return array<string, mixed> */
function app_config(): array
{
    return app()->config()->app();
}

function app_base_url(): string
{
    return app()->config()->baseUrl();
}
