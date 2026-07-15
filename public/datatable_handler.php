<?php

declare(strict_types=1);

// Standalone datatable AJAX endpoint (not routed).
// Injects EasyPHPDBCore's PDO so the datatable reuses the app connection and
// does not build its own (which would misread DATABASE_CHARSET, a full
// "SET NAMES ..." command, as a charset name -> error 2019).
require __DIR__ . '/../vendor/autoload.php';
Dotenv\Dotenv::createImmutable(__DIR__ . '/..')->load();
require __DIR__ . '/../config/App.php';

hstanleycrow\EasyPHPDatatables\SSP::handle(App\Core\Database::connection()->getConnection());
