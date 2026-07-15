<?php

declare(strict_types=1);

namespace Tests\Api;

use App\Core\Auth\JwtService;
use PHPUnit\Framework\TestCase;
use App\Middlewares\ApiAuthMiddleware;

final class ApiAuthMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        unset($_SERVER['HTTP_AUTHORIZATION'], $_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
    }

    protected function tearDown(): void
    {
        unset($_SERVER['HTTP_AUTHORIZATION'], $_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
    }

    public function test_valid_bearer_token_passes_and_exposes_claims(): void
    {
        $token = JwtService::fromEnv()->issue(['sub' => 1, 'username' => 'ada']);
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;

        $middleware = new ApiAuthMiddleware();

        $this->assertTrue($middleware->handle());
        $this->assertSame('ada', $middleware->claims['username']);
    }

    public function test_missing_header_fails(): void
    {
        $this->assertFalse((new ApiAuthMiddleware())->handle());
    }

    public function test_invalid_token_fails(): void
    {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer garbage.token.value';
        $this->assertFalse((new ApiAuthMiddleware())->handle());
    }

    public function test_non_bearer_scheme_fails(): void
    {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Basic dXNlcjpwYXNz';
        $this->assertFalse((new ApiAuthMiddleware())->handle());
    }
}
