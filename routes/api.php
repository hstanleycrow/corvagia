<?php

declare(strict_types=1);

use App\Core\Route;

# REST API routes (JSON)

# Auth (open): login issues an access + refresh token pair; refresh rotates it;
# logout revokes the refresh token.
Route::post('/api/auth/login/',   'Api/AuthController#login',   'api.auth.login');
Route::post('/api/auth/refresh/', 'Api/AuthController#refresh', 'api.auth.refresh');
Route::post('/api/auth/logout/',  'Api/AuthController#logout',  'api.auth.logout');

# Users resource (protected by JWT via the apiAuth middleware)
Route::get('/api/users/',           'Api/UsersController#index',   'api.users.index')->middleware('apiAuth');
Route::get('/api/users/[i:id]/',    'Api/UsersController#show',    'api.users.show')->middleware('apiAuth');
Route::post('/api/users/',          'Api/UsersController#store',   'api.users.store')->middleware('apiAuth');
Route::put('/api/users/[i:id]/',    'Api/UsersController#update',  'api.users.update')->middleware('apiAuth');
Route::delete('/api/users/[i:id]/', 'Api/UsersController#destroy', 'api.users.destroy')->middleware('apiAuth');
