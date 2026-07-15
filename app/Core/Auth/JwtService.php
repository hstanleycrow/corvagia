<?php

declare(strict_types=1);

namespace App\Core\Auth;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Core\Exceptions\ConfigurationException;

/**
 * Issues and verifies HS256 JSON Web Tokens.
 */
final class JwtService
{
    private const ALGORITHM = 'HS256';

    public function __construct(private string $secret, private int $ttl = 3600)
    {
        if ($this->secret === '') {
            throw new ConfigurationException('JWT_SECRET is not configured.');
        }
    }

    public static function fromEnv(): self
    {
        return new self(
            (string) ($_ENV['JWT_SECRET'] ?? ''),
            (int) ($_ENV['JWT_TTL'] ?? 3600)
        );
    }

    /**
     * @param array<string, mixed> $claims
     */
    public function issue(array $claims): string
    {
        $now = time();
        $payload = array_merge($claims, [
            'iat' => $now,
            'exp' => $now + $this->ttl,
        ]);

        return JWT::encode($payload, $this->secret, self::ALGORITHM);
    }

    /**
     * @return array<string, mixed>
     */
    public function verify(string $token): array
    {
        return (array) JWT::decode($token, new Key($this->secret, self::ALGORITHM));
    }

    public function ttl(): int
    {
        return $this->ttl;
    }
}
