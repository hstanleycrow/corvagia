<?php

declare(strict_types=1);

namespace App\Middlewares;

use Tests\Support\Spy;

class RecordingBlockMiddleware
{
    public function handle(): bool
    {
        Spy::record('mw:block');
        return false;
    }

    public function handleFailure(): void
    {
        Spy::record('mw:block:fail');
    }
}
