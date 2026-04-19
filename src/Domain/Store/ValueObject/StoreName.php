<?php

declare(strict_types=1);

namespace App\Domain\Store\ValueObject;

use App\Shared\Exception\ValidationException;

final readonly class StoreName
{
    public function __construct(private string $value)
    {
        if (trim($value) === '') {
            throw new ValidationException('Store name cannot be empty.');
        }
        if (strlen($value) > 100) {
            throw new ValidationException('Store name cannot exceed 100 characters.');
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
