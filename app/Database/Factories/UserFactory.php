<?php

declare(strict_types=1);

namespace App\Database\Factories;

use Models\User;
use Faker\Factory;
use App\Core\Database;
use App\Core\Initialize;

require dirname(__FILE__) . '/../../../vendor/autoload.php';

if ($argc != 2) {
    echo "Uso:\nphp app/Database/Factories/UserFactory.php <cantidad>\n";
    exit(1);
}

$count = (int) $argv[1];
Initialize::start(false);

$faker = Factory::create();
$connection = Database::connection();

for ($i = 0; $i < $count; $i++) :
    $name = $faker->name();
    $username = $faker->userName();
    $password = '123456';
    $data = [
        'name'     => $name,
        'username' => $username,
        'password' => $password,
        'active'   => 'S',
    ];
    try {
        $id = (new User($connection))->create($data)->lastInsertId();
        echo "Usuario creado: $id, $name, $username, $password\n";
    } catch (\Throwable $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
endfor;
