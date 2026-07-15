<?php

declare(strict_types=1);

namespace Tests\Routing;

use App\Core\Route;
use PHPUnit\Framework\TestCase;
use App\Core\Exceptions\RouteNotFoundException;
use App\Core\Exceptions\ControllerNotFoundException;

final class RouteNotFoundTest extends TestCase
{
    protected function setUp(): void
    {
        Route::reset();
    }

    public function test_unmatched_route_throws_route_not_found(): void
    {
        Route::get('/exists/', 'Test/RecordingController#index', 'exists');

        $this->expectException(RouteNotFoundException::class);
        Route::dispatch('/missing/', 'GET');
    }

    public function test_wrong_method_throws_route_not_found(): void
    {
        Route::get('/exists/', 'Test/RecordingController#index', 'exists');

        $this->expectException(RouteNotFoundException::class);
        Route::dispatch('/exists/', 'POST');
    }

    public function test_missing_controller_throws_controller_not_found(): void
    {
        Route::get('/broken/', 'Test/NoSuchController#index', 'broken');

        $this->expectException(ControllerNotFoundException::class);
        Route::dispatch('/broken/', 'GET');
    }

    public function test_missing_method_throws_controller_not_found(): void
    {
        Route::get('/broken/', 'Test/RecordingController#noSuchMethod', 'broken');

        $this->expectException(ControllerNotFoundException::class);
        Route::dispatch('/broken/', 'GET');
    }
}
