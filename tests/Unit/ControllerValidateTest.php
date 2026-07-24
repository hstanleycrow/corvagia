<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Controllers\Controller;
use Symfony\Component\HttpFoundation\Request;

final class ValidatingController extends Controller
{
    public function run(array $rules, array $sensitiveFields = []): void
    {
        $this->validate($rules, [], $sensitiveFields);
    }
}

final class ControllerValidateTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    private function controller(array $post): ValidatingController
    {
        return new ValidatingController(Request::create('/x', 'POST', $post), 'test.route');
    }

    public function test_validate_stores_all_fields_in_session_by_default(): void
    {
        $this->controller(['username' => 'ada', 'password' => 'secret'])
            ->run(['username' => 'required']);

        $this->assertSame(['username' => 'ada', 'password' => 'secret'], $_SESSION['formData']);
    }

    public function test_validate_excludes_sensitive_fields_from_session(): void
    {
        $this->controller(['username' => 'ada', 'password' => 'secret'])
            ->run(['username' => 'required'], ['password']);

        $this->assertSame(['username' => 'ada'], $_SESSION['formData']);
    }
}
