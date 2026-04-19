<?php

declare(strict_types=1);

namespace App\Domain\User\ValueObject;

use App\Shared\Exception\ValidationException;

final readonly class HashedPassword
{
    private string $hash;

    private function __construct(string $hash)
    {
        $this->hash = $hash;
    }

    public static function fromPlainText(string $plain): self
    {
        if (strlen($plain) < 8) {
            throw new ValidationException('Password must be at least 8 characters.');
        }
        return new self(password_hash($plain, PASSWORD_ARGON2ID));
    }

    public static function fromHash(string $hash): self
    {
        return new self($hash);
    }

    public function verify(string $plain): bool
    {
        return password_verify($plain, $this->hash);
    }

    public function getHash(): string
    {
        return $this->hash;
    }
}
