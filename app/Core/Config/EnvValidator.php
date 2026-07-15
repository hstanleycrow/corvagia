<?php

declare(strict_types=1);

namespace App\Core\Config;

use App\Core\Exceptions\ConfigurationException;

final class EnvValidator
{
    /**
     * @param array<string, mixed> $env
     * @param array<int, string> $required
     *
     * @throws ConfigurationException when a required key is absent or empty
     */
    public static function validate(array $env, array $required): void
    {
        $missing = [];
        foreach ($required as $key) {
            if (!array_key_exists($key, $env) || $env[$key] === '' || $env[$key] === null) {
                $missing[] = $key;
            }
        }

        if ($missing !== []) {
            throw new ConfigurationException('Missing required environment variables: ' . implode(', ', $missing));
        }
    }
}
