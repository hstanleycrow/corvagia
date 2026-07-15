<?php

declare(strict_types=1);

namespace App\Database;

use Models\User;
use App\Core\Database;
use App\Core\Initialize;

require dirname(__FILE__) . '/../../vendor/autoload.php';

Initialize::start(false);

// Default admin so a fresh install can reach the /admin/ panel. Change the
// password after the first login (or edit these before seeding).
$username = 'admin';
$password = 'admin1234';

$model = new User(Database::connection());

if ($model->isAvailableUser($username)) {
    echo "El usuario '{$username}' ya existe. Nada que sembrar.\n";
    exit(0);
}

$model->create([
    'name'     => 'Administrator',
    'username' => $username,
    'password' => $password,
    'active'   => 'S',
    'isAdmin'  => 'S',
]);

echo "Admin creado — usuario: {$username}, clave: {$password}. Cámbiala tras el primer ingreso.\n";
