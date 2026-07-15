<?php

declare(strict_types=1);

namespace App\Core;

class Csrf
{
    private int $length = 32;
    private ?string $token = null;
    private ?int $expiration = null;
    private int $expirationTime = 300; // 5 minutes

    public function __construct()
    {
        if (isset($_SESSION['csrf_token'])) {
            $this->token = $_SESSION['csrf_token']['token'];
            $this->expiration = $_SESSION['csrf_token']['expiration'];
        }
    }

    public function initToken(): self
    {
        if (!isset($_SESSION['csrf_token'])) {
            $this->generate();
            $_SESSION['csrf_token'] = [
                'token'      => $this->token,
                'expiration' => $this->expiration,
            ];
        }
        return $this;
    }

    private function generate(): self
    {
        $this->token = bin2hex(random_bytes($this->length));
        $this->expiration = time() + $this->expirationTime;
        return $this;
    }

    public static function validate(string $csrfToken, bool $validateExpiration = false): bool
    {
        $self = new self();

        if ($validateExpiration && $self->getExpiration() < time()) {
            return false;
        }

        $current = $self->getToken();
        return $current !== null && hash_equals($current, $csrfToken);
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function getExpiration(): ?int
    {
        return $this->expiration;
    }

    public static function clearCsrfToken(): self
    {
        unset($_SESSION['csrf_token']);
        return new self();
    }
}
