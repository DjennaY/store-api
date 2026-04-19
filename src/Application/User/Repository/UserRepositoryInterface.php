<?php

declare(strict_types=1);

namespace App\Application\User\Repository;

use App\Domain\User\Entity\User;
use App\Domain\User\ValueObject\UserEmail;
use App\Domain\User\ValueObject\UserId;

interface UserRepositoryInterface
{
    public function findById(UserId $id): ?User;

    public function findByEmail(UserEmail $email): ?User;

    public function save(User $user): void;

    public function existsByEmail(UserEmail $email): bool;
}
