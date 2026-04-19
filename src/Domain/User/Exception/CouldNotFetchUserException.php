<?php

declare(strict_types=1);

namespace App\Domain\User\Exception;

final class CouldNotFetchUserException extends \RuntimeException
{
    public function __construct(string $action, \Throwable $previous)
    {
        parent::__construct(
            sprintf('Could not fetch user during action "%s": %s', $action, $previous->getMessage()),
            0,
            $previous
        );
    }
}
