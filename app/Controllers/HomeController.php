<?php

declare(strict_types=1);

namespace App\Controllers;

use Models\User;
use App\Core\Database;
use App\Core\Template;

class HomeController extends Controller
{
    public function index(): void
    {
        $dbStatus = 'no conectada';
        $userCount = null;

        try {
            $userCount = (new User(Database::connection()))->countAll();
            $dbStatus = 'conectada';
        } catch (\Throwable $e) {
            $dbStatus = 'no conectada: ' . $e->getMessage();
        }

        Template::render('home', [
            'title'     => 'Corvagia',
            'dbStatus'  => $dbStatus,
            'userCount' => $userCount,
        ]);
    }
}
