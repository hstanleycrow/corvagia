<?php

declare(strict_types=1);

namespace Tests\Integration;

use Models\User;
use Tests\Support\SqliteConnection;
use PHPUnit\Framework\TestCase;
use hstanleycrow\EasyPHPDBCore\Exception\QueryException;

final class UserCrudEdgeCasesTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        $this->user = new User(new SqliteConnection());
    }

    public function test_get_by_id_returns_null_when_not_found(): void
    {
        $this->assertNull($this->user->getById(999));
    }

    public function test_find_by_username_returns_null_when_not_found(): void
    {
        $this->assertNull($this->user->findByUsername('nobody'));
    }

    public function test_duplicate_username_throws_query_exception(): void
    {
        $this->user->create(['name' => 'A', 'username' => 'dup', 'password' => 'x', 'active' => 'S']);

        $this->expectException(QueryException::class);
        $this->user->create(['name' => 'B', 'username' => 'dup', 'password' => 'y', 'active' => 'S']);
    }

    public function test_missing_required_column_throws_query_exception(): void
    {
        $this->expectException(QueryException::class);
        // 'name' is NOT NULL and absent.
        $this->user->create(['username' => 'no-name', 'password' => 'x', 'active' => 'S']);
    }
}
