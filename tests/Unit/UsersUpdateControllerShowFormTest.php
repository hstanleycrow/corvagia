<?php

declare(strict_types=1);

namespace App\Controllers;

/**
 * Overrides the global redirect() helper for this file only. Unqualified
 * function calls resolve within the *calling code's* namespace first, and
 * Controller::route() (which showForm()'s not-found branch goes through) lives
 * in App\Controllers — so this stub intercepts the redirect instead of the
 * real helper, which calls exit() and would kill the test process.
 */
function redirect(string $to): void
{
    $GLOBALS['__test_redirect_target'] = $to;
}

namespace Tests\Unit;

use App\Core\Route;
use App\Core\Database;
use PHPUnit\Framework\TestCase;
use Tests\Support\SqliteConnection;
use Symfony\Component\HttpFoundation\Request;
use App\Controllers\Admin\Users\UsersUpdateController;

final class UsersUpdateControllerShowFormTest extends TestCase
{
    protected function setUp(): void
    {
        Route::reset();
        Database::swap(new SqliteConnection());
        $_SESSION = [];
        unset($GLOBALS['__test_redirect_target']);

        Route::get('/admin/users/', 'Admin/Users/UsersIndexController#index', 'admin.usersList');
    }

    protected function tearDown(): void
    {
        Database::reset();
        unset($GLOBALS['__test_redirect_target']);
    }

    public function test_show_form_redirects_to_users_list_when_user_not_found(): void
    {
        $controller = new UsersUpdateController(Request::create('/admin/user/editar/999/'), 'admin.userEdit');

        $controller->showForm(999);

        $this->assertSame('/admin/users/', $GLOBALS['__test_redirect_target']);
    }

    public function test_show_form_sets_a_danger_flash_message_when_user_not_found(): void
    {
        $controller = new UsersUpdateController(Request::create('/admin/user/editar/999/'), 'admin.userEdit');

        $controller->showForm(999);

        $this->assertSame('El usuario solicitado no existe.', $_SESSION['flash_messages']['danger'] ?? null);
    }
}
