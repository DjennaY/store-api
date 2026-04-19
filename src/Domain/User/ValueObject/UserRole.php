<?php

declare(strict_types=1);

namespace App\Domain\User\ValueObject;

enum UserRole: string
{
    case ADMIN = 'admin';
    case USER = 'user';

    public function isAdmin(): bool
    {
        return $this === self::ADMIN;
    }
}
