<?php

declare(strict_types=1);

namespace App\Core\Http;

use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Builds the standard JSON envelope for the REST layer.
 *
 * Success: { "success": true, "data": ..., "meta": {...}? }
 * Error:   { "success": false, "error": { "code", "message", "details"? } }
 */
final class ApiResponse
{
    /**
     * @param array<string, mixed> $meta
     */
    public static function success(mixed $data = null, int $status = 200, array $meta = []): JsonResponse
    {
        $payload = ['success' => true, 'data' => $data];
        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        return new JsonResponse($payload, $status);
    }

    public static function noContent(): JsonResponse
    {
        $response = new JsonResponse(null, 204);
        $response->setContent('');

        return $response;
    }

    /**
     * @param array<string, array<int, string>> $details
     */
    public static function error(string $code, string $message, int $status, array $details = []): JsonResponse
    {
        $error = ['code' => $code, 'message' => $message];
        if ($details !== []) {
            $error['details'] = $details;
        }

        return new JsonResponse(['success' => false, 'error' => $error], $status);
    }
}
