<?php

declare(strict_types=1);

namespace App\Domain\User\Exception;

final class AdminUserConflictException extends \RuntimeException
{
    public function __construct(string $email)
    {
        parent::__construct(sprintf(
            'User "%s" already exists with role USER. Cannot create admin with this email.',
            $email,
        ));
    }
}
