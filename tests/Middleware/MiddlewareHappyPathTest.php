<?php

declare(strict_types=1);

namespace Tests\Middleware;

use App\Core\Route;
use Tests\Support\Spy;
use PHPUnit\Framework\TestCase;

final class MiddlewareHappyPathTest extends TestCase
{
    protected function setUp(): void
    {
        Route::reset();
        Spy::reset();
    }

    public function test_request_passes_all_middleware_then_reaches_controller(): void
    {
        Route::get('/guarded/', 'Test/RecordingController#guarded', 'guarded')
            ->middleware('recordingPass', 'recordingSecond');

        Route::dispatch('/guarded/', 'GET');

        $this->assertSame(['mw:pass', 'mw:second', 'guarded'], Spy::names());
    }
}
