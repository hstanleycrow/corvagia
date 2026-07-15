<?php

declare(strict_types=1);

namespace Tests\Support;

use PDO;
use hstanleycrow\EasyPHPDBCore\Connection\IConnection;

/**
 * In-memory SQLite connection implementing EasyPHPDBCore's IConnection, so the
 * User model can exercise the real CRUD classes without a MySQL server.
 */
final class SqliteConnection implements IConnection
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->migrate();
    }

    public function isConnected(): bool
    {
        return true;
    }

    public function getConnection(): ?PDO
    {
        return $this->pdo;
    }

    private function migrate(): void
    {
        $this->pdo->exec(
            "CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                username TEXT NOT NULL UNIQUE,
                password TEXT NOT NULL,
                active TEXT NOT NULL DEFAULT 'S',
                isAdmin TEXT NOT NULL DEFAULT 'N',
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            )"
        );

        $this->pdo->exec(
            "CREATE TABLE refresh_tokens (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                token_hash TEXT NOT NULL,
                expires_at TEXT NOT NULL,
                revoked_at TEXT DEFAULT NULL,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            )"
        );
    }
}
