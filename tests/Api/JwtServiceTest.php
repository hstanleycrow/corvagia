<?php

declare(strict_types=1);

namespace Tests\Api;

use UnexpectedValueException;
use App\Core\Auth\JwtService;
use Firebase\JWT\ExpiredException;
use PHPUnit\Framework\TestCase;
use Firebase\JWT\SignatureInvalidException;
use App\Core\Exceptions\ConfigurationException;

final class JwtServiceTest extends TestCase
{
    public function test_issue_and_verify_roundtrip(): void
    {
        $jwt = new JwtService('unit-test-secret-key-of-at-least-32-bytes', 3600);
        $claims = $jwt->verify($jwt->issue(['sub' => 5, 'username' => 'ada']));

        $this->assertSame(5, $claims['sub']);
        $this->assertSame('ada', $claims['username']);
        $this->assertArrayHasKey('exp', $claims);
        $this->assertArrayHasKey('iat', $claims);
    }

    public function test_expired_token_is_rejected(): void
    {
        $jwt = new JwtService('unit-test-secret-key-of-at-least-32-bytes', -10);
        $token = $jwt->issue(['sub' => 1]);

        $this->expectException(ExpiredException::class);
        $jwt->verify($token);
    }

    public function test_wrong_secret_is_rejected(): void
    {
        $token = (new JwtService('secret-a-padded-to-thirty-two-bytes-min'))->issue(['sub' => 1]);

        $this->expectException(SignatureInvalidException::class);
        (new JwtService('secret-b-padded-to-thirty-two-bytes-min'))->verify($token);
    }

    public function test_malformed_token_is_rejected(): void
    {
        $this->expectException(UnexpectedValueException::class);
        (new JwtService('unit-test-secret-key-of-at-least-32-bytes'))->verify('not-a-jwt');
    }

    public function test_empty_secret_throws_configuration_exception(): void
    {
        $this->expectException(ConfigurationException::class);
        new JwtService('');
    }
}
