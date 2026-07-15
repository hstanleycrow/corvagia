<?php

declare(strict_types=1);

namespace Tests\Api;

use App\Core\Database;
use Tests\Support\SqliteConnection;
use PHPUnit\Framework\TestCase;
use App\Core\Auth\RefreshTokenService;

final class RefreshTokenServiceTest extends TestCase
{
    protected function setUp(): void
    {
        Database::swap(new SqliteConnection());
    }

    protected function tearDown(): void
    {
        Database::reset();
    }

    private function service(int $ttl = 1209600): RefreshTokenService
    {
        return new RefreshTokenService($ttl);
    }

    public function test_issue_and_verify_roundtrip(): void
    {
        $service = $this->service();
        $token = $service->issue(7);

        $this->assertNotSame('', $token);
        $this->assertSame(7, $service->verify($token));
    }

    public function test_unknown_token_is_invalid(): void
    {
        $this->assertNull($this->service()->verify('does-not-exist'));
    }

    public function test_rotate_returns_new_token_and_revokes_the_old(): void
    {
        $service = $this->service();
        $old = $service->issue(3);

        $rotated = $service->rotate($old);

        $this->assertNotNull($rotated);
        $this->assertSame(3, $rotated['user_id']);
        $this->assertNotSame($old, $rotated['refresh_token']);
        $this->assertNull($service->verify($old), 'old token must be revoked after rotation');
        $this->assertSame(3, $service->verify($rotated['refresh_token']));
    }

    public function test_revoke_invalidates_the_token(): void
    {
        $service = $this->service();
        $token = $service->issue(1);

        $service->revoke($token);

        $this->assertNull($service->verify($token));
    }

    public function test_expired_token_is_invalid(): void
    {
        $service = $this->service(-10);
        $token = $service->issue(1);

        $this->assertNull($service->verify($token));
    }
}
