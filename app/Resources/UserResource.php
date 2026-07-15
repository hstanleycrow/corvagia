<?php

declare(strict_types=1);

namespace App\Resources;

/**
 * Public representation of a user row. Never exposes the password hash.
 */
final class UserResource
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $username,
        public readonly string $active,
        public readonly string $isAdmin,
        public readonly ?string $createdAt,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            (int) $row['id'],
            (string) $row['name'],
            (string) $row['username'],
            (string) ($row['active'] ?? 'S'),
            (string) ($row['isAdmin'] ?? 'N'),
            isset($row['created_at']) ? (string) $row['created_at'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'username'   => $this->username,
            'active'     => $this->active,
            'isAdmin'    => $this->isAdmin,
            'created_at' => $this->createdAt,
        ];
    }
}
