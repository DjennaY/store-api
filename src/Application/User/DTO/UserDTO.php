<?php

declare(strict_types=1);

namespace App\Application\User\DTO;

use App\Domain\User\Entity\User;

final readonly class UserDTO
{
    public function __construct(
        public string $id,
        public string $email,
        public string $firstName,
        public string $lastName,
        public string $role,
        public string $createdAt,
        public string $updatedAt,
    ) {
    }

    public static function fromEntity(User $user): self
    {
        return new self(
            id: $user->getId()->getValue(),
            email: $user->getEmail()->getValue(),
            firstName: $user->getFirstName(),
            lastName: $user->getLastName(),
            role: $user->getRole()->value,
            createdAt: $user->getCreatedAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $user->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
