<?php

declare(strict_types=1);

use App\Core\Route;

# Public routes

Route::get('/', 'HomeController#index', 'home');
