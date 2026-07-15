<?php

declare(strict_types=1);

namespace App\Core\Http;

use Symfony\Component\HttpFoundation\Response;

/**
 * CORS policy for the JSON API. Browsers calling /api/ from another origin send
 * a preflight OPTIONS request first (any request with an Authorization or
 * Content-Type: application/json header triggers it), so the bootstrap answers
 * that before routing and echoes the headers on the real request.
 *
 * Allowed origins come from the CORS_ALLOWED_ORIGINS env var (comma separated,
 * or `*`). Empty means no cross-origin access is granted.
 */
final class Cors
{
    public const ENV_KEY = 'CORS_ALLOWED_ORIGINS';

    private const ALLOWED_METHODS = 'GET, POST, PUT, PATCH, DELETE, OPTIONS';
    private const ALLOWED_HEADERS = 'Authorization, Content-Type, Accept';
    private const MAX_AGE = '86400';

    /**
     * The policy only covers the JSON API; web/admin pages are same-origin.
     */
    public static function applies(string $path): bool
    {
        return str_starts_with($path, '/api/');
    }

    /**
     * @param  array<string, mixed>  $env
     * @return list<string>
     */
    public static function allowedOrigins(array $env): array
    {
        $raw = trim((string) ($env[self::ENV_KEY] ?? ''));
        if ($raw === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }

    /**
     * The value for Access-Control-Allow-Origin, or null when the request's
     * origin is not allowed (the browser then blocks the response).
     *
     * @param  list<string>  $allowed
     */
    public static function resolveOrigin(?string $requestOrigin, array $allowed): ?string
    {
        if ($allowed === []) {
            return null;
        }

        if (in_array('*', $allowed, true)) {
            return '*';
        }

        if ($requestOrigin === null || $requestOrigin === '') {
            return null;
        }

        return in_array($requestOrigin, $allowed, true) ? $requestOrigin : null;
    }

    /**
     * @return array<string, string>
     */
    public static function headers(?string $allowOrigin): array
    {
        if ($allowOrigin === null) {
            return [];
        }

        $headers = [
            'Access-Control-Allow-Origin'  => $allowOrigin,
            'Access-Control-Allow-Methods' => self::ALLOWED_METHODS,
            'Access-Control-Allow-Headers' => self::ALLOWED_HEADERS,
            'Access-Control-Max-Age'       => self::MAX_AGE,
        ];

        // Responses differ per origin, so caches must key on it.
        if ($allowOrigin !== '*') {
            $headers['Vary'] = 'Origin';
        }

        return $headers;
    }

    /**
     * Answer to a preflight OPTIONS request: 204 with the policy headers.
     */
    public static function preflight(?string $allowOrigin): Response
    {
        return new Response('', Response::HTTP_NO_CONTENT, self::headers($allowOrigin));
    }
}
