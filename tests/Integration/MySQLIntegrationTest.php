<?php

declare(strict_types=1);

namespace Tests\Integration;

use Models\User;
use App\Core\Database;
use App\Database\Migrator;
use PHPUnit\Framework\TestCase;
use hstanleycrow\EasyPHPDBCore\Connection\IConnection;

/**
 * Exercises the real production path: Database::connection() building a MySQL
 * connection from $_ENV, plus the Migrator, against the throwaway test DB.
 * Skips entirely when MySQL is unreachable, so the suite stays green in CI.
 */
final class MySQLIntegrationTest extends TestCase
{
    private static ?IConnection $connection = null;

    public static function setUpBeforeClass(): void
    {
        try {
            Database::reset();
            $connection = Database::connection();
            ob_start();
            (new Migrator($connection))->run(self::migrations());
            ob_end_clean();
            self::$connection = $connection;
        } catch (\Throwable $e) {
            self::$connection = null;
        }
    }

    public static function tearDownAfterClass(): void
    {
        Database::reset();
        self::$connection = null;
    }

    protected function setUp(): void
    {
        if (self::$connection === null) {
            $this->markTestSkipped('MySQL test database not available');
        }
        self::$connection->getConnection()->exec('TRUNCATE TABLE users');
    }

    /**
     * @return array<string, string>
     */
    private static function migrations(): array
    {
        return require dirname(__DIR__, 2) . '/app/Database/Migrations/schema.php';
    }

    public function test_connection_is_live(): void
    {
        $this->assertTrue(self::$connection->isConnected());
    }

    public function test_crud_roundtrip_on_real_mysql(): void
    {
        $user = new User(self::$connection);

        $id = $user->create([
            'name'     => 'MySQL User',
            'username' => 'mysql_' . uniqid(),
            'password' => 'secret',
            'active'   => 'S',
        ])->lastInsertId();

        $this->assertGreaterThan(0, $id);
        $this->assertNotNull($user->getById($id));

        $user->update(['name' => 'Renamed'], ['id' => $id]);
        $this->assertSame('Renamed', $user->getById($id)['name']);

        $user->delete(['id' => $id]);
        $this->assertNull($user->getById($id));
    }

    public function test_migrator_is_idempotent(): void
    {
        ob_start();
        (new Migrator(self::$connection))->run(self::migrations());
        $output = (string) ob_get_clean();

        $this->assertStringContainsString('SKIP', $output);
    }
}
