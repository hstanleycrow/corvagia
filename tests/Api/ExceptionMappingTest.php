<?php

declare(strict_types=1);

namespace Tests\Api;

use Models\User;
use RuntimeException;
use Tests\Support\SqliteConnection;
use PHPUnit\Framework\TestCase;
use App\Core\Http\ExceptionHandler;
use App\Core\Exceptions\RouteNotFoundException;
use App\Core\Exceptions\ResourceNotFoundException;
use hstanleycrow\EasyPHPDBCore\Exception\QueryException;
use hstanleycrow\EasyPHPFormValidator\ValidationException;
use hstanleycrow\EasyPHPDBCore\Exception\ConnectionException;

final class ExceptionMappingTest extends TestCase
{
    private ExceptionHandler $handler;

    protected function setUp(): void
    {
        $this->handler = new ExceptionHandler();
    }

    /**
     * @return array{int, array<string, mixed>}
     */
    private function map(\Throwable $e): array
    {
        $response = $this->handler->toResponse($e);
        return [$response->getStatusCode(), json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR)];
    }

    public function test_resource_not_found_maps_to_404(): void
    {
        [$status, $body] = $this->map(new ResourceNotFoundException('User 9 not found.'));
        $this->assertSame(404, $status);
        $this->assertSame('not_found', $body['error']['code']);
        $this->assertSame('User 9 not found.', $body['error']['message']);
    }

    public function test_route_not_found_maps_to_404(): void
    {
        [$status, $body] = $this->map(new RouteNotFoundException('x'));
        $this->assertSame(404, $status);
        $this->assertSame('not_found', $body['error']['code']);
    }

    public function test_validation_maps_to_422_with_details(): void
    {
        [$status, $body] = $this->map(new ValidationException(['username' => ['Username is required']]));
        $this->assertSame(422, $status);
        $this->assertSame('validation_failed', $body['error']['code']);
        $this->assertSame(['username' => ['Username is required']], $body['error']['details']);
    }

    public function test_integrity_violation_maps_to_409(): void
    {
        // Produce a real integrity (SQLSTATE 23xxx) QueryException via SQLite.
        $user = new User(new SqliteConnection());
        $user->create(['name' => 'A', 'username' => 'dup', 'password' => 'x', 'active' => 'S']);

        try {
            $user->create(['name' => 'B', 'username' => 'dup', 'password' => 'y', 'active' => 'S']);
            $this->fail('Expected QueryException');
        } catch (QueryException $e) {
            [$status, $body] = $this->map($e);
            $this->assertSame(409, $status);
            $this->assertSame('conflict', $body['error']['code']);
        }
    }

    public function test_non_integrity_query_exception_maps_to_500(): void
    {
        [$status, $body] = $this->map(new QueryException('syntax error', 0));
        $this->assertSame(500, $status);
        $this->assertSame('server_error', $body['error']['code']);
    }

    public function test_connection_exception_maps_to_503(): void
    {
        [$status, $body] = $this->map(new ConnectionException('down'));
        $this->assertSame(503, $status);
        $this->assertSame('service_unavailable', $body['error']['code']);
    }

    public function test_generic_throwable_maps_to_500(): void
    {
        [$status, $body] = $this->map(new RuntimeException('boom'));
        $this->assertSame(500, $status);
        $this->assertSame('server_error', $body['error']['code']);
    }

    public function test_server_error_message_is_masked_outside_testing(): void
    {
        $original = $_ENV['ENVIRONMENT'];
        $_ENV['ENVIRONMENT'] = 'production';
        try {
            [$status, $body] = $this->map(new RuntimeException('sensitive detail'));
            $this->assertSame(500, $status);
            $this->assertSame('Internal server error.', $body['error']['message']);
        } finally {
            $_ENV['ENVIRONMENT'] = $original;
        }
    }
}
