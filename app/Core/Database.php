<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Logger\LoggerFactory;
use hstanleycrow\EasyPHPDBCore\Connection\IConnection;
use hstanleycrow\EasyPHPDBCore\Connection\MySQLEnvConfig;
use hstanleycrow\EasyPHPDBCore\Connection\MySQLPDOConnection;
use hstanleycrow\EasyPHPDBCore\Connection\MySQLEnvCharsetConfig;

class Database
{
    private static ?IConnection $connection = null;

    public static function connection(): IConnection
    {
        if (self::$connection === null) {
            self::$connection = new MySQLPDOConnection(
                new MySQLEnvConfig($_ENV),
                new MySQLEnvCharsetConfig($_ENV),
                LoggerFactory::create('database')
            );
        }

        return self::$connection;
    }

    /**
     * Drops the cached connection. Intended for test isolation.
     */
    public static function reset(): void
    {
        self::$connection = null;
    }

    /**
     * Replaces the shared connection with a given one. Intended for tests
     * (e.g. an in-memory SQLite connection).
     */
    public static function swap(IConnection $connection): void
    {
        self::$connection = $connection;
    }
}
