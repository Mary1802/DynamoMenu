<?php

declare(strict_types=1);

namespace App\Http;

final class ApiResponse
{
    /** @param array<string, mixed> $data */
    public static function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function error(string $message, int $status): never
    {
        self::json(['error' => $message], $status);
    }
}
