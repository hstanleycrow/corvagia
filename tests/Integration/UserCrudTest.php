<?php

declare(strict_types=1);

namespace Tests\Integration;

use Models\User;
use Tests\Support\SqliteConnection;
use PHPUnit\Framework\TestCase;

final class UserCrudTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        $this->user = new User(new SqliteConnection());
    }

    /**
     * @return array<string, string>
     */
    private function sampleData(string $username = 'ada'): array
    {
        return [
            'name'     => 'Ada Lovelace',
            'username' => $username,
            'password' => 'secret',
            'active'   => 'S',
        ];
    }

    public function test_create_returns_last_insert_id(): void
    {
        $id = $this->user->create($this->sampleData())->lastInsertId();
        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id);
    }

    public function test_get_by_id_returns_the_row(): void
    {
        $id = $this->user->create($this->sampleData())->lastInsertId();
        $row = $this->user->getById($id);

        $this->assertNotNull($row);
        $this->assertSame('ada', $row['username']);
        $this->assertSame('Ada Lovelace', $row['name']);
    }

    public function test_find_by_username_returns_the_row(): void
    {
        $this->user->create($this->sampleData('grace'));
        $row = $this->user->findByUsername('grace');

        $this->assertNotNull($row);
        $this->assertSame('grace', $row['username']);
    }

    public function test_password_is_hashed_on_create(): void
    {
        $id = $this->user->create($this->sampleData())->lastInsertId();
        $row = $this->user->getById($id);

        $this->assertNotSame('secret', $row['password']);
        $this->assertTrue(User::isValidPassword('secret', $row['password']));
    }

    public function test_update_modifies_the_row(): void
    {
        $id = $this->user->create($this->sampleData())->lastInsertId();
        $this->user->update(['name' => 'Ada L.'], ['id' => $id]);

        $this->assertSame('Ada L.', $this->user->getById($id)['name']);
    }

    public function test_delete_removes_the_row(): void
    {
        $id = $this->user->create($this->sampleData())->lastInsertId();
        $this->user->delete(['id' => $id]);

        $this->assertNull($this->user->getById($id));
    }

    public function test_count_all_reflects_inserts(): void
    {
        $this->assertSame(0, $this->user->countAll());
        $this->user->create($this->sampleData('a'));
        $this->user->create($this->sampleData('b'));
        $this->assertSame(2, $this->user->countAll());
    }
}
