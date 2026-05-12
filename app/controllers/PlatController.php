<?php

require_once __DIR__ . '/../models/Plat.php';

class PlatController
{
    public static function getMenuCategories(): array
    {
        return Plat::getByCategory();
    }

    public static function find(int $id): ?array
    {
        return Plat::getById($id);
    }
}

