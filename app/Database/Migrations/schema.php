<?php

declare(strict_types=1);

/**
 * Migrations definition: name => DDL statement.
 * Add new entries at the bottom. Names must be unique and are recorded once applied.
 */

return [
    '2026_07_11_create_users_table' =>
        "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            username VARCHAR(60) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            active CHAR(1) NOT NULL DEFAULT 'S',
            isAdmin CHAR(1) NOT NULL DEFAULT 'N',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    '2026_07_11_create_refresh_tokens_table' =>
        "CREATE TABLE IF NOT EXISTS refresh_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            token_hash CHAR(64) NOT NULL,
            expires_at DATETIME NOT NULL,
            revoked_at DATETIME NULL DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_refresh_token_hash (token_hash),
            INDEX idx_refresh_user_id (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
];
