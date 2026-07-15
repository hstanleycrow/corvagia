<?php

declare(strict_types=1);

namespace Tests\Routing;

use App\Core\Route;
use Tests\Support\Spy;
use PHPUnit\Framework\TestCase;

final class RouteVerbsTest extends TestCase
{
    protected function setUp(): void
    {
        Route::reset();
        Spy::reset();
    }

    public function test_get_dispatches_to_controller(): void
    {
        Route::get('/items/', 'Test/RecordingController#index', 'items.index');
        Route::dispatch('/items/', 'GET');
        $this->assertSame(['index'], Spy::names());
    }

    public function test_post_dispatches_to_controller(): void
    {
        Route::post('/items/', 'Test/RecordingController#store', 'items.store');
        Route::dispatch('/items/', 'POST');
        $this->assertSame(['store'], Spy::names());
    }

    public function test_put_dispatches_to_controller(): void
    {
        Route::put('/items/[i:id]/', 'Test/RecordingController#replace', 'items.replace');
        Route::dispatch('/items/5/', 'PUT');
        $this->assertSame([['name' => 'replace', 'args' => ['5']]], Spy::all());
    }

    public function test_delete_dispatches_to_controller(): void
    {
        Route::delete('/items/[i:id]/', 'Test/RecordingController#destroy', 'items.destroy');
        Route::dispatch('/items/9/', 'DELETE');
        $this->assertSame([['name' => 'destroy', 'args' => ['9']]], Spy::all());
    }

    public function test_patch_dispatches_to_controller(): void
    {
        Route::patch('/items/[i:id]/', 'Test/RecordingController#patchItem', 'items.patch');
        Route::dispatch('/items/3/', 'PATCH');
        $this->assertSame([['name' => 'patchItem', 'args' => ['3']]], Spy::all());
    }

    public function test_same_uri_different_verb_dispatches_independently(): void
    {
        Route::get('/items/', 'Test/RecordingController#index', 'items.index');
        Route::post('/items/', 'Test/RecordingController#store', 'items.store');
        Route::dispatch('/items/', 'POST');
        $this->assertSame(['store'], Spy::names());
    }
}
