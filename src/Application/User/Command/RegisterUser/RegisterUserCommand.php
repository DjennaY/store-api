<?php

declare(strict_types=1);

namespace App\Application\User\Command\RegisterUser;

use App\Application\Shared\Exception\InvalidCommandException;
use Webmozart\Assert\Assert;

final readonly class RegisterUserCommand
{
    private function __construct(
        public string $firstName,
        public string $lastName,
        public string $email,
        public string $plainPassword,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function validateAndCreate(array $data): self
    {
        $errors = [];

        try {
            Assert::stringNotEmpty($data['first_name'] ?? '', '"first_name" must be a non-empty string.');
        } catch (\InvalidArgumentException $e) {
            $errors['first_name'] = $e->getMessage();
        }

        try {
            Assert::stringNotEmpty($data['last_name'] ?? '', '"last_name" must be a non-empty string.');
        } catch (\InvalidArgumentException $e) {
            $errors['last_name'] = $e->getMessage();
        }

        try {
            Assert::email($data['email'] ?? '', '"email" must be a valid email address.');
        } catch (\InvalidArgumentException $e) {
            $errors['email'] = $e->getMessage();
        }

        try {
            Assert::string($data['password'] ?? '', '"password" must be a string.');
            Assert::minLength($data['password'] ?? '', 8, '"password" must be at least 8 characters.');
        } catch (\InvalidArgumentException $e) {
            $errors['password'] = $e->getMessage();
        }

        if ($errors !== []) {
            throw new InvalidCommandException($errors);
        }

        return new self(
            firstName: trim($data['first_name']),
            lastName: trim($data['last_name']),
            email: strtolower(trim($data['email'])),
            plainPassword: $data['password'],
        );
    }
}
