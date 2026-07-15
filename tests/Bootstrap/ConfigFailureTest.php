<?php

declare(strict_types=1);

namespace Tests\Bootstrap;

use PHPUnit\Framework\TestCase;
use App\Core\Config\EnvValidator;
use App\Core\Exceptions\ConfigurationException;

final class ConfigFailureTest extends TestCase
{
    public function test_missing_required_key_throws(): void
    {
        $this->expectException(ConfigurationException::class);
        EnvValidator::validate(['ENVIRONMENT' => 'testing'], ['ENVIRONMENT', 'DATABASE_HOST']);
    }

    public function test_message_lists_every_missing_key(): void
    {
        try {
            EnvValidator::validate([], ['DATABASE_HOST', 'AUTH_SALT']);
            $this->fail('Expected ConfigurationException was not thrown');
        } catch (ConfigurationException $e) {
            $this->assertStringContainsString('DATABASE_HOST', $e->getMessage());
            $this->assertStringContainsString('AUTH_SALT', $e->getMessage());
        }
    }

    public function test_empty_value_counts_as_missing(): void
    {
        $this->expectException(ConfigurationException::class);
        EnvValidator::validate(['DATABASE_NAME' => ''], ['DATABASE_NAME']);
    }

    public function test_complete_env_passes(): void
    {
        EnvValidator::validate(['A' => '1', 'B' => '2'], ['A', 'B']);
        $this->addToAssertionCount(1);
    }
}
