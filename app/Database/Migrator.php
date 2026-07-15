<?php

declare(strict_types=1);

namespace App\Database;

use PDO;
use PDOException;
use hstanleycrow\EasyPHPDBCore\Connection\IConnection;

/**
 * Basic migration runner. Applies an ordered array of named DDL statements
 * and tracks them in a `migrations` table so re-runs skip what's already done.
 */
class Migrator
{
    public function __construct(private IConnection $connection)
    {
    }

    /**
     * @param array<string, string> $migrations name => DDL statement
     */
    public function run(array $migrations): void
    {
        $pdo = $this->connection->getConnection();
        $this->ensureMigrationsTable($pdo);
        $applied = $this->appliedMigrations($pdo);

        foreach ($migrations as $name => $ddl) {
            if (in_array($name, $applied, true)) {
                echo "SKIP  $name\n";
                continue;
            }
            try {
                $pdo->exec($ddl);
                $pdo->prepare('INSERT INTO migrations (name) VALUES (?)')->execute([$name]);
                echo "OK    $name\n";
            } catch (PDOException $e) {
                echo "ERROR $name: " . $e->getMessage() . "\n";
                break;
            }
        }
    }

    private function ensureMigrationsTable(PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS migrations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(191) NOT NULL UNIQUE,
                applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    /**
     * @return array<int, string>
     */
    private function appliedMigrations(PDO $pdo): array
    {
        return $pdo->query('SELECT name FROM migrations')->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }
}
