<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

// Authoritative test environment. Points the DB at the throwaway MySQL test
// database (root, no password) and provides every key config/App.php needs.
$_ENV = array_merge($_ENV, [
    'ENVIRONMENT'       => 'testing',
    'AUTH_SALT'         => 'test-salt',
    'BUSINESS_NAME'     => 'Corvagia',
    'BASE_URL'          => 'http://localhost/',
    'REPORT_ERROR_EMAIL' => 'test@example.com',
    'JWT_SECRET'        => 'test-jwt-secret-that-is-at-least-32-bytes-long',
    'JWT_TTL'           => '3600',
    'REFRESH_TTL'       => '1209600',
    'PREPROS_ACTIVE'    => '',
    'PREPROS_PORT'      => '8848',
    'DIR_BASE'          => dirname(__DIR__) . DIRECTORY_SEPARATOR,
    'DATABASE_HOST'     => '127.0.0.1',
    'DATABASE_NAME'     => 'db_corvagia_test',
    'DATABASE_USERNAME' => 'root',
    'DATABASE_PASSWORD' => '',
    'DATABASE_PORT'     => '3306',
    'DATABASE_CHARSET'  => "SET NAMES 'utf8mb4' COLLATE utf8mb4_unicode_ci",
]);

// Defines framework constants (AUTH_SALT, BUSINESS_NAME, DIR_BASE, DIR_BASE_LOGS...).
require __DIR__ . '/../config/App.php';
