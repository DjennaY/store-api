<?php

declare(strict_types=1);

namespace App\Domain\User\Exception;

/**
 * Thrown by the repository when User persistence fails.
 * Wraps the original Throwable without exposing the infrastructure stack trace.
 */
final class CouldNotSaveUserException extends \RuntimeException
{
    public function __construct(string $action, \Throwable $previous)
    {
        parent::__construct(
            sprintf('Could not save user during action "%s": %s', $action, $previous->getMessage()),
            0,
            $previous
        );
    }
}
