<?php

declare(strict_types=1);

namespace Tests\Middleware;

use Tests\Support\Spy;
use App\Core\MiddlewareRunner;
use PHPUnit\Framework\TestCase;

final class MiddlewareOrderTest extends TestCase
{
    protected function setUp(): void
    {
        Spy::reset();
    }

    public function test_middlewares_run_in_registration_order(): void
    {
        $runner = new MiddlewareRunner();
        $passed = $runner->run(['recordingPass', 'recordingSecond']);

        $this->assertTrue($passed);
        $this->assertSame(['recordingPass', 'recordingSecond'], $runner->executed);
        $this->assertSame(['mw:pass', 'mw:second'], Spy::names());
        $this->assertNull($runner->failed);
    }
}
