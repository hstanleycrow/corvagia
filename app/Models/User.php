<?php

declare(strict_types=1);

namespace Models;

use hstanleycrow\EasyPHPDBCore\Model;

class User extends Model
{
    protected ?string $table = 'users';

    public function create(array $fieldsList): self
    {
        if (isset($fieldsList['password'])) {
            $fieldsList['password'] = self::passwordHash($fieldsList['password'] . AUTH_SALT);
        }
        parent::create($fieldsList);
        return $this;
    }

    public function findByUsername(string $username): ?array
    {
        $rows = $this->query("SELECT * FROM {$this->table} WHERE username = ?")->getRecords([$username]);
        return $rows[0] ?? null;
    }

    /**
     * True when the username already exists (i.e. it is taken).
     */
    public function isAvailableUser(string $username): bool
    {
        return $this->findByUsername($username) !== null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        return $this->query("SELECT * FROM {$this->table} ORDER BY id")->getRecords();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function paginate(int $page, int $perPage): array
    {
        // $page/$perPage are already validated ints, safe to inline (PDO cannot
        // reliably bind LIMIT/OFFSET across drivers).
        $offset = ($page - 1) * $perPage;
        return $this->query("SELECT * FROM {$this->table} ORDER BY id LIMIT {$perPage} OFFSET {$offset}")->getRecords();
    }

    public function countAll(): int
    {
        $rows = $this->query("SELECT COUNT(*) AS total FROM {$this->table}")->getRecords();
        return (int) ($rows[0]['total'] ?? 0);
    }

    public static function isValidPassword(string $inputPassword, string $dbPassword): bool
    {
        return password_verify($inputPassword . AUTH_SALT, $dbPassword);
    }

    public static function passwordHash(string $plainPassword): string
    {
        return password_hash($plainPassword, PASSWORD_DEFAULT);
    }
}
