<?php

declare(strict_types=1);

namespace App\Domain\User\ValueObject;

use App\Shared\Exception\ValidationException;

final readonly class UserEmail
{
    private string $value;

    public function __construct(string $email)
    {
        $normalized = strtolower(trim($email));
        if (!filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException('Invalid email format.');
        }
        $this->value = $normalized;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
