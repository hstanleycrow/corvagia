<?php

declare(strict_types=1);

use App\Core\Route;

# Admin panel scaffold

# Auth: login validates against the users table and starts the session.
Route::get('/login/',         'Auth/LoginController#showForm', 'login');
Route::post('/login/',        'Auth/LoginController#login',    'login.attempt');
Route::get('/admin/logout/',  'Auth/LoginController#logout',   'admin.logout');

# Dashboard (requires an authenticated admin session).
Route::get('/admin/', 'Admin/DashboardController#index', 'admin.dashboard')->middleware('auth', 'admin');

# Users CRUD (reference admin CRUD — mirror this to add your own resources).
Route::get('/admin/users/',              'Admin/Users/UsersIndexController#index',     'admin.usersList')->middleware('auth', 'admin');
Route::get('/admin/user/agregar/',       'Admin/Users/UsersCreateController#showForm', 'admin.showUserAddForm')->middleware('auth', 'admin');
Route::post('/admin/user/agregar/',      'Admin/Users/UsersCreateController#create',   'admin.createUser')->middleware('auth', 'admin');
Route::get('/admin/user/editar/[i:id]/', 'Admin/Users/UsersUpdateController#showForm', 'admin.showUserEditForm')->middleware('auth', 'admin');
Route::post('/admin/user/editar/[i:id]/','Admin/Users/UsersUpdateController#save',     'admin.saveUser')->middleware('auth', 'admin');
Route::get('/admin/user/borrar/[i:id]/', 'Admin/Users/UsersDeleteController#delete',   'admin.deleteUser')->middleware('auth', 'admin');
