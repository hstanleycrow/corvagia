<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Csrf;
use PHPUnit\Framework\TestCase;

final class CsrfTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    public function test_init_token_generates_and_persists_a_token(): void
    {
        $csrf = (new Csrf())->initToken();

        $this->assertNotNull($csrf->getToken());
        $this->assertArrayHasKey('csrf_token', $_SESSION);
        $this->assertSame($csrf->getToken(), $_SESSION['csrf_token']['token']);
    }

    public function test_init_token_is_idempotent(): void
    {
        (new Csrf())->initToken();
        $first = $_SESSION['csrf_token']['token'];
        (new Csrf())->initToken();

        $this->assertSame($first, $_SESSION['csrf_token']['token']);
    }

    public function test_validate_accepts_matching_token(): void
    {
        (new Csrf())->initToken();
        $token = $_SESSION['csrf_token']['token'];

        $this->assertTrue(Csrf::validate($token));
    }

    public function test_validate_rejects_wrong_token(): void
    {
        (new Csrf())->initToken();

        $this->assertFalse(Csrf::validate('not-the-token'));
    }

    public function test_validate_rejects_expired_token(): void
    {
        (new Csrf())->initToken();
        $token = $_SESSION['csrf_token']['token'];
        $_SESSION['csrf_token']['expiration'] = time() - 10;

        $this->assertFalse(Csrf::validate($token, true));
    }

    public function test_clear_removes_the_token(): void
    {
        (new Csrf())->initToken();
        Csrf::clearCsrfToken();

        $this->assertArrayNotHasKey('csrf_token', $_SESSION);
    }
}
