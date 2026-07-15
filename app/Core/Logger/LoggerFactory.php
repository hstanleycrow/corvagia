<?php

declare(strict_types=1);

namespace App\Core\Logger;

use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;

class LoggerFactory
{
    public static function create(string $channel = 'app'): LoggerInterface
    {
        $handler = new StreamHandler(DIR_BASE_LOGS . '/error.log');
        $handler->setFormatter(new LineFormatter("[%datetime%] %channel%.%level_name%: %message%\n"));

        $logger = new Logger($channel);
        $logger->pushHandler($handler);

        return $logger;
    }
}
