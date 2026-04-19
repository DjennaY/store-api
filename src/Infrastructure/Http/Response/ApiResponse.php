<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Response;

/**
 * Uniform response format for all endpoints:
 * { "success": bool, "message": string|object, "data": mixed }
 */
final class ApiResponse
{
    public static function success(mixed $data = null, int $status = 200, string $message = 'OK'): void
    {
        self::send(['success' => true, 'message' => $message, 'data' => $data], $status);
    }

    public static function created(mixed $data = null): void
    {
        self::send(['success' => true, 'message' => 'Created', 'data' => $data], 201);
    }

    public static function noContent(): void
    {
        http_response_code(204);
        exit;
    }

    /** @param string|array<string, string> $message */
    public static function error(string|array $message, int $status = 400): void
    {
        self::send(['success' => false, 'message' => $message, 'data' => null], $status);
    }

    /** @param array<string, mixed> $payload */
    private static function send(array $payload, int $status): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
