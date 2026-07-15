<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Initialize;
use App\Core\Http\ExceptionHandler;

try {
    $app = Initialize::start();
} catch (\Throwable $e) {
    // Routing-level failures on /api are returned as JSON; the web side keeps
    // its current behavior (the exception bubbles up).
    $handler = new ExceptionHandler();
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    if ($handler->isApiRequest($path)) {
        $handler->toResponse($e)->send();
    } else {
        throw $e;
    }
}
