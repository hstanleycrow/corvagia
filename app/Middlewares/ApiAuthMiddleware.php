<?php

declare(strict_types=1);

namespace App\Middlewares;

use Throwable;
use App\Core\Auth\JwtService;
use App\Core\Http\ApiResponse;

/**
 * Guards API routes with a Bearer JWT. On failure it emits a JSON 401 instead
 * of redirecting.
 */
class ApiAuthMiddleware
{
    /** @var array<string, mixed>|null */
    public ?array $claims = null;

    public function handle(): bool
    {
        $token = $this->bearerToken();
        if ($token === null) {
            return false;
        }

        try {
            $this->claims = JwtService::fromEnv()->verify($token);
            return true;
        } catch (Throwable) {
            return false;
        }
    }

    public function handleFailure(): void
    {
        ApiResponse::error('unauthorized', 'Authentication required.', 401)->send();
        exit;
    }

    private function bearerToken(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION']
            ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
            ?? '';

        if (is_string($header) && preg_match('/^Bearer\s+(.+)$/i', $header, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }
}
