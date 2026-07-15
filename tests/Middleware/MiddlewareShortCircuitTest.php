<?php

declare(strict_types=1);

namespace Tests\Middleware;

use Tests\Support\Spy;
use App\Core\MiddlewareRunner;
use PHPUnit\Framework\TestCase;
use App\Middlewares\RecordingBlockMiddleware;
use App\Core\Exceptions\MiddlewareException;

final class MiddlewareShortCircuitTest extends TestCase
{
    protected function setUp(): void
    {
        Spy::reset();
    }

    public function test_chain_stops_at_first_failing_middleware(): void
    {
        $runner = new MiddlewareRunner();
        $passed = $runner->run(['recordingBlock', 'recordingPass']);

        $this->assertFalse($passed);
        $this->assertSame(['recordingBlock'], $runner->executed);
        $this->assertSame(['mw:block'], Spy::names());
        $this->assertInstanceOf(RecordingBlockMiddleware::class, $runner->failed);
    }

    public function test_unknown_middleware_throws(): void
    {
        $this->expectException(MiddlewareException::class);
        (new MiddlewareRunner())->run(['doesNotExist']);
    }
}
