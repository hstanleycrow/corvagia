<?php

declare(strict_types=1);

namespace Tests\Api;

use Models\User;
use App\Core\Database;
use Tests\Support\SqliteConnection;
use PHPUnit\Framework\TestCase;
use App\Controllers\Api\UsersController;
use Symfony\Component\HttpFoundation\Request;

final class UsersEndpointTest extends TestCase
{
    protected function setUp(): void
    {
        Database::swap(new SqliteConnection());
    }

    protected function tearDown(): void
    {
        Database::reset();
    }

    private function seed(string $username = 'ada'): int
    {
        return (new User(Database::connection()))->create([
            'name'     => 'Ada Lovelace',
            'username' => $username,
            'password' => 'secret',
            'active'   => 'S',
        ])->lastInsertId();
    }

    /**
     * @param array<string, mixed> $body
     */
    private function request(string $method, array $body = []): Request
    {
        return Request::create(
            '/api/users/',
            $method,
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $body === [] ? '' : (string) json_encode($body)
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function json(string $content): array
    {
        return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    }

    private function get(string $uri = '/api/users/'): UsersController
    {
        return new UsersController(Request::create($uri, 'GET'));
    }

    public function test_index_returns_all_users_without_password(): void
    {
        $this->seed('a');
        $this->seed('b');

        $response = $this->get()->index();
        $body = $this->json($response->getContent());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertCount(2, $body['data']);
        $this->assertArrayNotHasKey('password', $body['data'][0]);
    }

    public function test_index_returns_pagination_meta(): void
    {
        $this->seed('a');
        $this->seed('b');
        $this->seed('c');

        $body = $this->json($this->get('/api/users/?page=1&per_page=2')->index()->getContent());

        $this->assertCount(2, $body['data']);
        $this->assertSame(['page' => 1, 'per_page' => 2, 'total' => 3, 'total_pages' => 2], $body['meta']);
    }

    public function test_index_second_page_returns_remainder(): void
    {
        $this->seed('a');
        $this->seed('b');
        $this->seed('c');

        $body = $this->json($this->get('/api/users/?page=2&per_page=2')->index()->getContent());

        $this->assertCount(1, $body['data']);
        $this->assertSame(2, $body['meta']['page']);
    }

    public function test_per_page_is_capped(): void
    {
        $this->seed('a');

        $body = $this->json($this->get('/api/users/?per_page=1000')->index()->getContent());

        $this->assertSame(100, $body['meta']['per_page']);
    }

    public function test_index_empty_page_returns_no_rows(): void
    {
        $this->seed('a');

        $body = $this->json($this->get('/api/users/?page=5&per_page=10')->index()->getContent());

        $this->assertCount(0, $body['data']);
        $this->assertSame(1, $body['meta']['total']);
    }

    public function test_show_returns_the_user(): void
    {
        $id = $this->seed();

        $response = (new UsersController($this->request('GET')))->show($id);
        $body = $this->json($response->getContent());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('ada', $body['data']['username']);
        $this->assertArrayNotHasKey('password', $body['data']);
    }

    public function test_show_returns_404_when_missing(): void
    {
        $response = (new UsersController($this->request('GET')))->show(999);
        $body = $this->json($response->getContent());

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('not_found', $body['error']['code']);
    }

    public function test_store_creates_a_user(): void
    {
        $request = $this->request('POST', ['name' => 'Grace', 'username' => 'grace', 'password' => 'pw']);
        $response = (new UsersController($request))->store();
        $body = $this->json($response->getContent());

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('grace', $body['data']['username']);
        $this->assertGreaterThan(0, $body['data']['id']);
        $this->assertArrayNotHasKey('password', $body['data']);
    }

    public function test_store_fails_validation(): void
    {
        $response = (new UsersController($this->request('POST', [])))->store();
        $body = $this->json($response->getContent());

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('validation_failed', $body['error']['code']);
        $this->assertArrayHasKey('details', $body['error']);
    }

    public function test_store_duplicate_username_conflicts(): void
    {
        $this->seed('dup');
        $request = $this->request('POST', ['name' => 'X', 'username' => 'dup', 'password' => 'pw']);
        $response = (new UsersController($request))->store();
        $body = $this->json($response->getContent());

        $this->assertSame(409, $response->getStatusCode());
        $this->assertSame('conflict', $body['error']['code']);
    }

    public function test_update_modifies_the_user(): void
    {
        $id = $this->seed();
        $request = $this->request('PUT', ['name' => 'Ada L.']);
        $response = (new UsersController($request))->update($id);
        $body = $this->json($response->getContent());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Ada L.', $body['data']['name']);
    }

    public function test_update_returns_404_when_missing(): void
    {
        $request = $this->request('PUT', ['name' => 'X']);
        $response = (new UsersController($request))->update(999);

        $this->assertSame(404, $response->getStatusCode());
    }

    public function test_update_fails_validation(): void
    {
        $id = $this->seed();
        $response = (new UsersController($this->request('PUT', [])))->update($id);
        $body = $this->json($response->getContent());

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('validation_failed', $body['error']['code']);
    }

    public function test_destroy_removes_the_user(): void
    {
        $id = $this->seed();
        $response = (new UsersController($this->request('DELETE')))->destroy($id);

        $this->assertSame(204, $response->getStatusCode());
        $this->assertNull((new User(Database::connection()))->getById($id));
    }

    public function test_destroy_returns_404_when_missing(): void
    {
        $response = (new UsersController($this->request('DELETE')))->destroy(999);

        $this->assertSame(404, $response->getStatusCode());
    }
}
