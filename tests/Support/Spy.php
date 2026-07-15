<?php

declare(strict_types=1);

namespace Tests\Support;

/**
 * Records ordered calls from fixture controllers and middleware so tests can
 * assert what ran and in what order.
 */
final class Spy
{
    /** @var array<int, array{name: string, args: array<int, mixed>}> */
    private static array $calls = [];

    /**
     * @param array<int, mixed> $args
     */
    public static function record(string $name, array $args = []): void
    {
        self::$calls[] = ['name' => $name, 'args' => $args];
    }

    /** @return array<int, array{name: string, args: array<int, mixed>}> */
    public static function all(): array
    {
        return self::$calls;
    }

    /** @return array<int, string> */
    public static function names(): array
    {
        return array_map(static fn (array $c): string => $c['name'], self::$calls);
    }

    public static function reset(): void
    {
        self::$calls = [];
    }
}
