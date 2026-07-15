<?php

declare(strict_types=1);

namespace Tests\Api;

use App\Core\Http\Cors;
use PHPUnit\Framework\TestCase;

final class CorsTest extends TestCase
{
    public function test_policy_only_covers_api_paths(): void
    {
        $this->assertTrue(Cors::applies('/api/users/'));
        $this->assertFalse(Cors::applies('/admin/users/'));
        $this->assertFalse(Cors::applies('/'));
    }

    public function test_allowed_origins_are_parsed_from_env(): void
    {
        $env = ['CORS_ALLOWED_ORIGINS' => 'http://localhost:3000, https://app.example.com'];

        $this->assertSame(
            ['http://localhost:3000', 'https://app.example.com'],
            Cors::allowedOrigins($env)
        );
    }

    public function test_missing_or_empty_env_allows_nothing(): void
    {
        $this->assertSame([], Cors::allowedOrigins([]));
        $this->assertSame([], Cors::allowedOrigins(['CORS_ALLOWED_ORIGINS' => '   ']));
    }

    public function test_allowed_origin_is_echoed_back(): void
    {
        $allowed = ['http://localhost:3000'];

        $this->assertSame(
            'http://localhost:3000',
            Cors::resolveOrigin('http://localhost:3000', $allowed)
        );
    }

    public function test_unknown_origin_is_rejected(): void
    {
        $this->assertNull(Cors::resolveOrigin('http://evil.example', ['http://localhost:3000']));
    }

    public function test_wildcard_allows_any_origin(): void
    {
        $this->assertSame('*', Cors::resolveOrigin('http://anything.example', ['*']));
    }

    public function test_no_allowed_origins_rejects_everything(): void
    {
        $this->assertNull(Cors::resolveOrigin('http://localhost:3000', []));
    }

    public function test_request_without_origin_is_rejected_unless_wildcard(): void
    {
        $this->assertNull(Cors::resolveOrigin(null, ['http://localhost:3000']));
        $this->assertSame('*', Cors::resolveOrigin(null, ['*']));
    }

    public function test_headers_expose_the_policy_and_vary_on_origin(): void
    {
        $headers = Cors::headers('http://localhost:3000');

        $this->assertSame('http://localhost:3000', $headers['Access-Control-Allow-Origin']);
        $this->assertStringContainsString('DELETE', $headers['Access-Control-Allow-Methods']);
        $this->assertStringContainsString('OPTIONS', $headers['Access-Control-Allow-Methods']);
        $this->assertStringContainsString('Authorization', $headers['Access-Control-Allow-Headers']);
        $this->assertSame('Origin', $headers['Vary']);
    }

    public function test_wildcard_headers_do_not_vary(): void
    {
        $headers = Cors::headers('*');

        $this->assertSame('*', $headers['Access-Control-Allow-Origin']);
        $this->assertArrayNotHasKey('Vary', $headers);
    }

    public function test_rejected_origin_produces_no_headers(): void
    {
        $this->assertSame([], Cors::headers(null));
    }

    public function test_preflight_returns_204_with_headers(): void
    {
        $response = Cors::preflight('http://localhost:3000');

        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame('http://localhost:3000', $response->headers->get('Access-Control-Allow-Origin'));
    }

    public function test_preflight_for_rejected_origin_has_no_allow_origin_header(): void
    {
        $response = Cors::preflight(null);

        $this->assertSame(204, $response->getStatusCode());
        $this->assertNull($response->headers->get('Access-Control-Allow-Origin'));
    }
}
