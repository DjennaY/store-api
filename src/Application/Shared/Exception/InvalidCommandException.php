<?php

declare(strict_types=1);

namespace App\Application\Shared\Exception;

/**
 * Thrown by validateAndCreate() when assertions fail.
 * Holds an associative array of all field errors.
 *
 * @see \Webmozart\Assert\Assert
 */
final class InvalidCommandException extends \RuntimeException
{
    /**
     * @param array<string, string> $errors [ 'field_name' => 'error message' ]
     */
    public function __construct(private readonly array $errors)
    {
        parent::__construct('Command validation failed.');
    }

    /** @return array<string, string> */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
