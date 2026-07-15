<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Template;
use App\Controllers\Controller;

class DashboardController extends Controller
{
    public function index(): void
    {
        Template::render('admin/dashboard', [
            'title' => 'Dashboard',
        ]);
    }
}
