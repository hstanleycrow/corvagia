<?php

declare(strict_types=1);

namespace Models;

use hstanleycrow\EasyPHPDBCore\Model;

class RefreshToken extends Model
{
    protected ?string $table = 'refresh_tokens';

    /**
     * @return array<string, mixed>|null
     */
    public function findByHash(string $hash): ?array
    {
        $rows = $this->query("SELECT * FROM {$this->table} WHERE token_hash = ?")->getRecords([$hash]);
        return $rows[0] ?? null;
    }
}
