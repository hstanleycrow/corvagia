<?php

declare(strict_types=1);

namespace Tests\Api;

use Models\User;
use App\Core\Database;
use Tests\Support\SqliteConnection;
use PHPUnit\Framework\TestCase;
use App\Controllers\Api\AuthController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

final class RefreshLogoutEndpointTest extends TestCase
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
    private function req(array $body): Request
    {
        return Request::create('/api/auth/', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], (string) json_encode($body));
    }

    /**
     * @return array<string, mixed>
     */
    private function json(JsonResponse $response): array
    {
        return json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }

    private function loginRefreshToken(): string
    {
        $response = (new AuthController($this->req(['username' => 'ada', 'password' => 'secret'])))->login();
        return $this->json($response)['data']['refresh_token'];
    }

    public function test_refresh_rotates_and_returns_a_new_pair(): void
    {
        $old = $this->loginRefreshToken();

        $response = (new AuthController($this->req(['refresh_token' => $old])))->refresh();
        $body = $this->json($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertArrayHasKey('access_token', $body['data']);
        $this->assertNotSame($old, $body['data']['refresh_token']);
    }

    public function test_rotated_token_cannot_be_reused(): void
    {
        $old = $this->loginRefreshToken();
        (new AuthController($this->req(['refresh_token' => $old])))->refresh();

        $response = (new AuthController($this->req(['refresh_token' => $old])))->refresh();

        $this->assertSame(401, $response->getStatusCode());
    }

    public function test_invalid_refresh_token_is_unauthorized(): void
    {
        $response = (new AuthController($this->req(['refresh_token' => 'nope'])))->refresh();
        $body = $this->json($response);

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame('unauthorized', $body['error']['code']);
    }

    public function test_refresh_requires_the_token_field(): void
    {
        $response = (new AuthController($this->req([])))->refresh();

        $this->assertSame(422, $response->getStatusCode());
    }

    public function test_logout_revokes_the_refresh_token(): void
    {
        $token = $this->loginRefreshToken();

        $logout = (new AuthController($this->req(['refresh_token' => $token])))->logout();
        $this->assertSame(204, $logout->getStatusCode());

        $refresh = (new AuthController($this->req(['refresh_token' => $token])))->refresh();
        $this->assertSame(401, $refresh->getStatusCode());
    }
}
