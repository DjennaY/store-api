<?php

declare(strict_types=1);

namespace App\Infrastructure\Auth;

use App\Domain\User\ValueObject\UserRole;

final readonly class AuthContext
{
    public function __construct(
        public string $userId,
        public UserRole $role,
    ) {
    }

    public function isAdmin(): bool
    {
        return $this->role->isAdmin();
    }
}
