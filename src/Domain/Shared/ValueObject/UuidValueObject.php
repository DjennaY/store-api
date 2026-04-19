<?php

declare(strict_types=1);

namespace App\Domain\Shared\ValueObject;

use App\Shared\Exception\ValidationException;

abstract class UuidValueObject
{
    public function __construct(private readonly string $value)
    {
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value)) {
            throw new ValidationException(sprintf('Invalid UUID: %s', $value));
        }
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
