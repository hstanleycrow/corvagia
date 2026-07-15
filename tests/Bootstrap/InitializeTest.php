<?php

declare(strict_types=1);

namespace Tests\Bootstrap;

use App\Core\Initialize;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\PreserveGlobalState;

final class InitializeTest extends TestCase
{
    public function test_essential_constants_are_defined_after_bootstrap(): void
    {
        $this->assertTrue(defined('DIR_BASE'));
        $this->assertTrue(defined('DIR_BASE_LOGS'));
        $this->assertTrue(defined('AUTH_SALT'));
        $this->assertTrue(defined('BUSINESS_NAME'));
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_app_boots_without_error_when_config_is_valid(): void
    {
        $app = Initialize::start(false);
        $this->assertInstanceOf(Initialize::class, $app);
    }
}
