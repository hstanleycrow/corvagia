<?php

declare(strict_types=1);

namespace Tests\Api;

use Models\User;
use App\Core\Database;
use App\Core\Auth\JwtService;
use Tests\Support\SqliteConnection;
use PHPUnit\Framework\TestCase;
use App\Controllers\Api\AuthController;
use Symfony\Component\HttpFoundation\Request;

final class AuthEndpointTest extends TestCase
{
    protected function setUp(): void
    {
        Database::swap(new SqliteConnection());
        (new User(Database::connection()))->create([
            'name'     => 'Ada',
            'username' => 'ada',
            'password' => 'secret',
            'active'   => 'S',
        ]);
    }

    protected function tearDown(): void
    {
        Database::reset();
    }

    /**
     * @param array<string, mixed> $body
     */
    private function login(array $body): \Symfony\Component\HttpFoundation\JsonResponse
    {
        $request = Request::create(
            '/api/auth/login/',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            (string) json_encode($body)
        );

        return (new AuthController($request))->login();
    }

    /**
     * @return array<string, mixed>
     */
    private function json(string $content): array
    {
        return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    }

    public function test_valid_credentials_return_a_token_pair(): void
    {
        $response = $this->login(['username' => 'ada', 'password' => 'secret']);
        $body = $this->json($response->getContent());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertArrayHasKey('access_token', $body['data']);
        $this->assertArrayHasKey('refresh_token', $body['data']);
        $this->assertSame('Bearer', $body['data']['token_type']);
        $this->assertSame(3600, $body['data']['expires_in']);

        // The access token verifies and carries the user identity.
        $claims = JwtService::fromEnv()->verify($body['data']['access_token']);
        $this->assertSame('ada', $claims['username']);
    }

    public function test_wrong_password_is_unauthorized(): void
    {
        $response = $this->login(['username' => 'ada', 'password' => 'nope']);
        $body = $this->json($response->getContent());

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame('unauthorized', $body['error']['code']);
    }

    public function test_unknown_user_is_unauthorized(): void
    {
        $response = $this->login(['username' => 'ghost', 'password' => 'secret']);

        $this->assertSame(401, $response->getStatusCode());
    }

    public function test_missing_fields_fail_validation(): void
    {
        $response = $this->login([]);
        $body = $this->json($response->getContent());

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('validation_failed', $body['error']['code']);
    }
}
