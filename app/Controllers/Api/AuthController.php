<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use Models\User;
use App\Core\Database;
use App\Core\Auth\JwtService;
use App\Core\Http\ApiResponse;
use App\Core\Auth\RefreshTokenService;
use Symfony\Component\HttpFoundation\JsonResponse;

final class AuthController extends ApiController
{
    public function login(): JsonResponse
    {
        return $this->handle(function (): JsonResponse {
            $data = $this->input();
            $this->validate($data, [
                'username' => 'required',
                'password' => 'required',
            ]);

            $user = (new User(Database::connection()))->findByUsername((string) $data['username']);
            if ($user === null || !User::isValidPassword((string) $data['password'], (string) $user['password'])) {
                return ApiResponse::error('unauthorized', 'Invalid credentials.', 401);
            }

            $refresh = RefreshTokenService::fromEnv()->issue((int) $user['id']);

            return ApiResponse::success($this->tokens((int) $user['id'], (string) $user['username'], $refresh));
        });
    }

    public function refresh(): JsonResponse
    {
        return $this->handle(function (): JsonResponse {
            $data = $this->input();
            $this->validate($data, ['refresh_token' => 'required']);

            $rotated = RefreshTokenService::fromEnv()->rotate((string) $data['refresh_token']);
            if ($rotated === null) {
                return ApiResponse::error('unauthorized', 'Invalid refresh token.', 401);
            }

            $user = (new User(Database::connection()))->getById($rotated['user_id']);
            if ($user === null) {
                return ApiResponse::error('unauthorized', 'Invalid refresh token.', 401);
            }

            return ApiResponse::success($this->tokens($rotated['user_id'], (string) $user['username'], $rotated['refresh_token']));
        });
    }

    public function logout(): JsonResponse
    {
        return $this->handle(function (): JsonResponse {
            $data = $this->input();
            $this->validate($data, ['refresh_token' => 'required']);

            RefreshTokenService::fromEnv()->revoke((string) $data['refresh_token']);

            return ApiResponse::noContent();
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function tokens(int $userId, string $username, string $refreshToken): array
    {
        $jwt = JwtService::fromEnv();

        return [
            'access_token'  => $jwt->issue(['sub' => $userId, 'username' => $username]),
            'refresh_token' => $refreshToken,
            'token_type'    => 'Bearer',
            'expires_in'    => $jwt->ttl(),
        ];
    }
}
