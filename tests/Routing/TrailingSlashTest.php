<?php

declare(strict_types=1);

namespace Tests\Routing;

use App\Core\Route;
use PHPUnit\Framework\TestCase;

final class TrailingSlashTest extends TestCase
{
    public function test_path_without_slash_gets_canonical_target(): void
    {
        $this->assertSame('/admin/', Route::canonicalTarget('/admin'));
    }

    public function test_path_with_slash_is_already_canonical(): void
    {
        $this->assertNull(Route::canonicalTarget('/admin/'));
    }

    public function test_root_is_canonical(): void
    {
        $this->assertNull(Route::canonicalTarget('/'));
    }

    public function test_file_like_path_is_left_untouched(): void
    {
        $this->assertNull(Route::canonicalTarget('/assets/app.css'));
        $this->assertNull(Route::canonicalTarget('/docs/manual.pdf'));
    }

    public function test_query_string_is_preserved(): void
    {
        $this->assertSame('/search/?q=x', Route::canonicalTarget('/search', 'q=x'));
    }

    public function test_non_get_dispatch_rewrites_instead_of_redirecting(): void
    {
        Route::reset();
        \Tests\Support\Spy::reset();
        Route::post('/items/', 'Test/RecordingController#store', 'items.store');

        // No trailing slash + POST -> internal rewrite, no exit, controller runs.
        Route::dispatch('/items', 'POST');

        $this->assertSame(['store'], \Tests\Support\Spy::names());
    }
}
