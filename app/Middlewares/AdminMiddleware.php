<?php

declare(strict_types=1);

namespace App\Middlewares;

class AdminMiddleware
{
    public function handle(): bool
    {
        return isset($_SESSION['userdat']['isAdmin']) && $_SESSION['userdat']['isAdmin'] === 'S';
    }

    public function handleFailure(): void
    {
        redirect('/login/');
        exit;
    }
}
