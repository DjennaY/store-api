<?php

declare(strict_types=1);

namespace App\Application\User\Command\CreateAdmin;

use App\Application\Shared\Exception\InvalidCommandException;
use Webmozart\Assert\Assert;

final readonly class CreateAdminCommand
{
    private function __construct(
        public string $email,
        public string $plainPassword,
        public string $firstName,
        public string $lastName,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function validateAndCreate(array $data): self
    {
        $errors = [];

        try {
            Assert::email(trim((string)($data['email'] ?? '')), '"email" must be a valid email address.');
        } catch (\InvalidArgumentException $e) {
            $errors['email'] = $e->getMessage();
        }

        try {
            Assert::minLength((string)($data['password'] ?? ''), 8, '"password" must be at least 8 characters.');
        } catch (\InvalidArgumentException $e) {
            $errors['password'] = $e->getMessage();
        }

        try {
            Assert::stringNotEmpty((string)($data['first_name'] ?? ''), '"first_name" is required.');
        } catch (\InvalidArgumentException $e) {
            $errors['first_name'] = $e->getMessage();
        }

        try {
            Assert::stringNotEmpty((string)($data['last_name'] ?? ''), '"last_name" is required.');
        } catch (\InvalidArgumentException $e) {
            $errors['last_name'] = $e->getMessage();
        }

        if ($errors !== []) {
            throw new InvalidCommandException($errors);
        }

        return new self(
            email: trim((string)$data['email']),
            plainPassword: (string)$data['password'],
            firstName: trim((string)$data['first_name']),
            lastName: trim((string)$data['last_name']),
        );
    }
}
