<?php

declare(strict_types=1);

namespace Tests\Api;

use App\Core\Http\ApiResponse;
use PHPUnit\Framework\TestCase;

final class ApiResponseTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function decode(string $json): array
    {
        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }

    public function test_success_envelope(): void
    {
        $response = ApiResponse::success(['id' => 1]);
        $body = $this->decode($response->getContent());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($body['success']);
        $this->assertSame(['id' => 1], $body['data']);
        $this->assertArrayNotHasKey('meta', $body);
    }

    public function test_success_with_status_and_meta(): void
    {
        $response = ApiResponse::success([], 201, ['total' => 3]);
        $body = $this->decode($response->getContent());

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame(['total' => 3], $body['meta']);
    }

    public function test_no_content(): void
    {
        $response = ApiResponse::noContent();

        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame('', $response->getContent());
    }

    public function test_error_envelope(): void
    {
        $response = ApiResponse::error('not_found', 'Missing', 404);
        $body = $this->decode($response->getContent());

        $this->assertSame(404, $response->getStatusCode());
        $this->assertFalse($body['success']);
        $this->assertSame('not_found', $body['error']['code']);
        $this->assertSame('Missing', $body['error']['message']);
        $this->assertArrayNotHasKey('details', $body['error']);
    }

    public function test_error_with_details(): void
    {
        $response = ApiResponse::error('validation_failed', 'Invalid', 422, ['name' => ['Required']]);
        $body = $this->decode($response->getContent());

        $this->assertSame(['name' => ['Required']], $body['error']['details']);
    }
}
