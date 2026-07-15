<?php

declare(strict_types=1);

namespace App\Core;

use League\Plates\Engine;

class Template
{
    private static ?Engine $template = null;

    private static function getTemplate(): Engine
    {
        if (self::$template === null) {
            self::$template = new Engine(DIR_BASE . 'resources/views');
        }
        return self::$template;
    }

    public static function render(string $view, array $data = []): void
    {
        echo self::getTemplate()->render($view, $data);
    }
}
