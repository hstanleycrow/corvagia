<?php

declare(strict_types=1);

namespace Tests\Routing;

use App\Core\Route;
use Tests\Support\Spy;
use PHPUnit\Framework\TestCase;

final class RouteParamsTest extends TestCase
{
    protected function setUp(): void
    {
        Route::reset();
        Spy::reset();
    }

    public function test_integer_param_reaches_controller(): void
    {
        Route::get('/users/[i:id]/', 'Test/RecordingController#show', 'users.show');
        Route::dispatch('/users/42/', 'GET');
        $this->assertSame([['name' => 'show', 'args' => ['42']]], Spy::all());
    }

    public function test_param_is_cast_to_declared_int_type(): void
    {
        Route::get('/users/[i:id]/', 'Test/RecordingController#showTyped', 'users.showTyped');
        Route::dispatch('/users/42/', 'GET');
        $this->assertSame([['name' => 'showTyped', 'args' => [42]]], Spy::all());
    }

    public function test_extra_param_is_ignored_when_method_declares_none(): void
    {
        Route::post('/users/[i:id]/', 'Test/RecordingController#store', 'users.store');
        Route::dispatch('/users/42/', 'POST');
        $this->assertSame([['name' => 'store', 'args' => []]], Spy::all());
    }

    public function test_string_param_reaches_controller(): void
    {
        Route::get('/menu/[*:slug]/', 'Test/RecordingController#show', 'menu.show');
        Route::dispatch('/menu/hello-world/', 'GET');
        $this->assertSame([['name' => 'show', 'args' => ['hello-world']]], Spy::all());
    }

    public function test_query_string_is_ignored_for_matching(): void
    {
        Route::get('/users/[i:id]/', 'Test/RecordingController#show', 'users.show');
        Route::dispatch('/users/7/?ref=home', 'GET');
        $this->assertSame([['name' => 'show', 'args' => ['7']]], Spy::all());
    }
}
