<?php

declare(strict_types=1);

namespace App\Database;

use App\Core\Database;
use App\Core\Initialize;

require dirname(__FILE__) . '/../../vendor/autoload.php';

Initialize::start(false);

$migrations = require __DIR__ . '/Migrations/schema.php';

try {
    (new Migrator(Database::connection()))->run($migrations);
    echo "Migraciones completadas.\n";
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
