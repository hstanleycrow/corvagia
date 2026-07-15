<?php

declare(strict_types=1);

namespace App\Core\Auth;

use App\Core\Database;
use Models\RefreshToken;

/**
 * Stateful, rotating refresh tokens. The plaintext token is returned once to
 * the client; only its SHA-256 hash is stored. Rotation revokes the old token
 * and issues a new one on every refresh.
 */
final class RefreshTokenService
{
    public function __construct(private int $ttl)
    {
    }

    public static function fromEnv(): self
    {
        return new self((int) ($_ENV['REFRESH_TTL'] ?? 1209600));
    }

    public function issue(int $userId): string
    {
        $token = bin2hex(random_bytes(32));
        $this->model()->create([
            'user_id'    => $userId,
            'token_hash' => $this->hash($token),
            'expires_at' => date('Y-m-d H:i:s', time() + $this->ttl),
        ]);

        return $token;
    }

    public function verify(string $token): ?int
    {
        $row = $this->findValid($token);
        return $row !== null ? (int) $row['user_id'] : null;
    }

    /**
     * Rotates a valid token: revokes it and issues a fresh one.
     *
     * @return array{user_id: int, refresh_token: string}|null
     */
    public function rotate(string $token): ?array
    {
        $row = $this->findValid($token);
        if ($row === null) {
            return null;
        }

        $userId = (int) $row['user_id'];
        $this->revokeById((int) $row['id']);

        return ['user_id' => $userId, 'refresh_token' => $this->issue($userId)];
    }

    public function revoke(string $token): void
    {
        $row = $this->model()->findByHash($this->hash($token));
        if ($row !== null && $row['revoked_at'] === null) {
            $this->revokeById((int) $row['id']);
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findValid(string $token): ?array
    {
        $row = $this->model()->findByHash($this->hash($token));
        if ($row === null || $row['revoked_at'] !== null || strtotime((string) $row['expires_at']) <= time()) {
            return null;
        }

        return $row;
    }

    private function revokeById(int $id): void
    {
        $this->model()->update(['revoked_at' => date('Y-m-d H:i:s')], ['id' => $id]);
    }

    private function hash(string $token): string
    {
        return hash('sha256', $token);
    }

    private function model(): RefreshToken
    {
        return new RefreshToken(Database::connection());
    }
}
