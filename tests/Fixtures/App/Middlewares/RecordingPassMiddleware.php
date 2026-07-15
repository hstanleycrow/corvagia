<?php

declare(strict_types=1);

namespace App\Middlewares;

use Tests\Support\Spy;

class RecordingPassMiddleware
{
    public function handle(): bool
    {
        Spy::record('mw:pass');
        return true;
    }

    public function handleFailure(): void
    {
        Spy::record('mw:pass:fail');
    }
}
