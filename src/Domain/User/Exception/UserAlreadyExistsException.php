<?php

declare(strict_types=1);

namespace App\Domain\User\Exception;

final class UserAlreadyExistsException extends \RuntimeException
{
    public function __construct(string $email)
    {
        parent::__construct(sprintf('User already exists with email: %s', $email));
    }
}
