<?php

declare(strict_types=1);

namespace App\Domain\Store\Exception;

/**
 * Thrown by the repository when Store persistence fails.
 * Wraps the original Throwable without exposing the infrastructure stack trace.
 */
final class CouldNotSaveStoreException extends \RuntimeException
{
    public function __construct(string $action, \Throwable $previous)
    {
        parent::__construct(
            sprintf('Could not save store during action "%s": %s', $action, $previous->getMessage()),
            0,
            $previous
        );
    }
}
