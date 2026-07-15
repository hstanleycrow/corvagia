<?php

declare(strict_types=1);

namespace App\Middlewares;

use Tests\Support\Spy;

class RecordingSecondMiddleware
{
    public function handle(): bool
    {
        Spy::record('mw:second');
        return true;
    }

    public function handleFailure(): void
    {
        Spy::record('mw:second:fail');
    }
}
