<?php

declare(strict_types=1);

namespace App\Controllers\Test;

use Tests\Support\Spy;
use App\Controllers\Controller;

/**
 * Test-only controller resolved by the router as Test/RecordingController.
 * Each action records its name and arguments through the Spy.
 */
class RecordingController extends Controller
{
    public function index(): void
    {
        Spy::record('index');
    }

    public function store(): void
    {
        Spy::record('store');
    }

    public function replace(string $id): void
    {
        Spy::record('replace', [$id]);
    }

    public function destroy(string $id): void
    {
        Spy::record('destroy', [$id]);
    }

    public function patchItem(string $id): void
    {
        Spy::record('patchItem', [$id]);
    }

    public function show(string $id): void
    {
        Spy::record('show', [$id]);
    }

    public function showTyped(int $id): void
    {
        Spy::record('showTyped', [$id]);
    }

    public function guarded(): void
    {
        Spy::record('guarded');
    }
}
