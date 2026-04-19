<?php

declare(strict_types=1);

namespace App\Domain\User\Exception;

final class UserNotFoundException extends \RuntimeException
{
    public function __construct(string $identifier)
    {
        parent::__construct(sprintf('User not found: %s', $identifier));
    }
}
