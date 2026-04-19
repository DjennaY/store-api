<?php

declare(strict_types=1);

namespace App\Domain\User\Entity;

use App\Domain\User\ValueObject\HashedPassword;
use App\Domain\User\ValueObject\UserEmail;
use App\Domain\User\ValueObject\UserId;
use App\Domain\User\ValueObject\UserRole;

final class User
{
    private function __construct(
        private readonly UserId $id,
        private readonly UserEmail $email,
        private readonly string $firstName,
        private readonly string $lastName,
        private HashedPassword $password,
        private readonly UserRole $role,
        private readonly \DateTimeImmutable $createdAt,
        private \DateTimeImmutable $updatedAt,
    ) {
    }

    public static function register(
        UserId $id,
        UserEmail $email,
        string $firstName,
        string $lastName,
        HashedPassword $password,
    ): self {
        $now = new \DateTimeImmutable();
        return new self($id, $email, $firstName, $lastName, $password, UserRole::USER, $now, $now);
    }

    public static function createAdmin(
        UserId $id,
        UserEmail $email,
        string $firstName,
        string $lastName,
        HashedPassword $password,
    ): self {
        $now = new \DateTimeImmutable();
        return new self($id, $email, $firstName, $lastName, $password, UserRole::ADMIN, $now, $now);
    }

    public static function reconstitute(
        UserId $id,
        UserEmail $email,
        string $firstName,
        string $lastName,
        HashedPassword $password,
        UserRole $role,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
    ): self {
        return new self($id, $email, $firstName, $lastName, $password, $role, $createdAt, $updatedAt);
    }

    public function getId(): UserId
    {
        return $this->id;
    }

    public function getEmail(): UserEmail
    {
        return $this->email;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getPassword(): HashedPassword
    {
        return $this->password;
    }

    public function getRole(): UserRole
    {
        return $this->role;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
