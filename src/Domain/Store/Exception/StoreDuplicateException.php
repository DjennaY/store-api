<?php

declare(strict_types=1);

namespace App\Domain\Store\Exception;

/**
 * Thrown when a store creation or update conflicts with an existing natural key.
 * Carries the duplicate store ID to return in the X-Existing-Store-Id header.
 */
final class StoreDuplicateException extends \RuntimeException
{
    public function __construct(private readonly string $existingId)
    {
        parent::__construct('A store with the same name and address already exists.');
    }

    public function getExistingId(): string
    {
        return $this->existingId;
    }
}
