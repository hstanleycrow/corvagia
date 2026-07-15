<?php

declare(strict_types=1);

namespace Tests\Routing;

use App\Core\Route;
use PHPUnit\Framework\TestCase;

final class UrlGenerationTest extends TestCase
{
    protected function setUp(): void
    {
        Route::reset();
    }

    public function test_generates_url_with_param(): void
    {
        Route::get('/users/[i:id]/', 'Test/RecordingController#show', 'users.show');
        $this->assertSame('/users/7/', Route::getUrlFromName('users.show', ['id' => 7]));
    }

    public function test_generates_static_url(): void
    {
        Route::get('/home/', 'Test/RecordingController#index', 'home');
        $this->assertSame('/home/', Route::getUrlFromName('home'));
    }
}
