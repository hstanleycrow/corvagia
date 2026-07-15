<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Exceptions\MiddlewareException;

/**
 * Resolves and runs a middleware chain by name, stopping at the first that
 * fails. It records execution order and the failing instance without emitting
 * any HTTP side effect, so the flow can be asserted in isolation.
 */
class MiddlewareRunner
{
    /** @var array<int, string> */
    public array $executed = [];

    public ?object $failed = null;

    /**
     * @param array<int, string> $names middleware short names (e.g. 'auth')
     */
    public function run(array $names): bool
    {
        foreach ($names as $name) {
            $class = 'App\\Middlewares\\' . ucfirst($name) . 'Middleware';
            if (!class_exists($class)) {
                throw new MiddlewareException("Invalid middleware: {$name}");
            }

            $instance = new $class();
            $this->executed[] = $name;

            if (!$instance->handle()) {
                $this->failed = $instance;
                return false;
            }
        }

        return true;
    }
}
