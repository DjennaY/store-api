<?php

declare(strict_types=1);

namespace App\Application\Store\Query\GetStore;

use App\Application\Shared\Exception\InvalidCommandException;
use Webmozart\Assert\Assert;

final readonly class GetStoreQuery
{
    private function __construct(
        public string $id,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function validateAndCreate(array $data): self
    {
        $errors = [];

        try {
            Assert::stringNotEmpty(trim((string)($data['id'] ?? '')), '"id" is required.');
        } catch (\InvalidArgumentException $e) {
            $errors['id'] = $e->getMessage();
        }

        if ($errors !== []) {
            throw new InvalidCommandException($errors);
        }

        return new self(id: trim((string)$data['id']));
    }
}
