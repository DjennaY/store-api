<?php

declare(strict_types=1);

namespace App\Application\User\Command\LoginUser;

use App\Application\Shared\Exception\InvalidCommandException;
use Webmozart\Assert\Assert;

final readonly class LoginUserCommand
{
    private function __construct(
        public string $email,
        public string $plainPassword,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function validateAndCreate(array $data): self
    {
        $errors = [];

        try {
            Assert::email($data['email'] ?? '', '"email" must be a valid email address.');
        } catch (\InvalidArgumentException $e) {
            $errors['email'] = $e->getMessage();
        }

        try {
            Assert::stringNotEmpty($data['password'] ?? '', '"password" is required.');
        } catch (\InvalidArgumentException $e) {
            $errors['password'] = $e->getMessage();
        }

        if ($errors !== []) {
            throw new InvalidCommandException($errors);
        }

        return new self(
            email: strtolower(trim($data['email'])),
            plainPassword: $data['password'],
        );
    }
}
