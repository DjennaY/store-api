<?php

declare(strict_types=1);

namespace App\Application\Store\Command\DeleteStore;

use App\Application\Shared\Exception\InvalidCommandException;
use Webmozart\Assert\Assert;

final readonly class DeleteStoreCommand
{
    private function __construct(
        public string $id,
        public string $requestedBy,
        public bool $isAdmin,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function validateAndCreate(array $data): self
    {
        $errors = [];

        try {
            Assert::stringNotEmpty(trim($data['id'] ?? ''), 'Store "id" is required.');
        } catch (\InvalidArgumentException $e) {
            $errors['id'] = $e->getMessage();
        }

        try {
            Assert::stringNotEmpty($data['requested_by'] ?? '', 'Authenticated user is required.');
        } catch (\InvalidArgumentException $e) {
            $errors['requested_by'] = $e->getMessage();
        }

        if ($errors !== []) {
            throw new InvalidCommandException($errors);
        }

        return new self(
            id: $data['id'],
            requestedBy: $data['requested_by'],
            isAdmin: (bool)($data['is_admin'] ?? false),
        );
    }
}
