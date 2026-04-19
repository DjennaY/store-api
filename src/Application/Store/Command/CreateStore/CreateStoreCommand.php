<?php

declare(strict_types=1);

namespace App\Application\Store\Command\CreateStore;

use App\Application\Shared\Exception\InvalidCommandException;
use Webmozart\Assert\Assert;

final readonly class CreateStoreCommand
{
    private function __construct(
        public string $name,
        public string $address,
        public string $city,
        public string $zipCode,
        public string $countryIso,
        public string $phone,
        public string $createdBy,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function validateAndCreate(array $data): self
    {
        $errors = [];

        try {
            $name = (string)($data['name'] ?? '');
            Assert::stringNotEmpty($name, '"name" is required.');
            Assert::maxLength($name, 100, '"name" cannot exceed 100 characters.');
        } catch (\InvalidArgumentException $e) {
            $errors['name'] = $e->getMessage();
        }

        try {
            Assert::stringNotEmpty($data['address'] ?? '', '"address" is required.');
        } catch (\InvalidArgumentException $e) {
            $errors['address'] = $e->getMessage();
        }

        try {
            Assert::stringNotEmpty($data['city'] ?? '', '"city" is required.');
        } catch (\InvalidArgumentException $e) {
            $errors['city'] = $e->getMessage();
        }

        try {
            Assert::regex(trim($data['zip_code'] ?? ''), '/^\d{5}$/', '"zip_code" must be exactly 5 digits.');
        } catch (\InvalidArgumentException $e) {
            $errors['zip_code'] = $e->getMessage();
        }

        try {
            Assert::regex(trim($data['country_iso'] ?? ''), '/^[A-Za-z]{2}$/', '"country_iso" must be a 2-letter ISO code (e.g. FR).');
        } catch (\InvalidArgumentException $e) {
            $errors['country_iso'] = $e->getMessage();
        }

        try {
            Assert::regex(trim($data['phone'] ?? ''), '/^\+?[\d\s\-]{7,15}$/', '"phone" is invalid (7-15 digits, +, spaces, dashes allowed).');
        } catch (\InvalidArgumentException $e) {
            $errors['phone'] = $e->getMessage();
        }

        try {
            Assert::stringNotEmpty($data['created_by'] ?? '', 'Authenticated user is required.');
        } catch (\InvalidArgumentException $e) {
            $errors['created_by'] = $e->getMessage();
        }

        if ($errors !== []) {
            throw new InvalidCommandException($errors);
        }

        return new self(
            name: trim($data['name']),
            address: trim($data['address']),
            city: trim($data['city']),
            zipCode: trim($data['zip_code']),
            countryIso: strtoupper(trim($data['country_iso'])),
            phone: trim($data['phone']),
            createdBy: $data['created_by'],
        );
    }
}
