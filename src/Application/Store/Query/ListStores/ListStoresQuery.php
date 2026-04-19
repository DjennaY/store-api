<?php

declare(strict_types=1);

namespace App\Application\Store\Query\ListStores;

use App\Application\Shared\Exception\InvalidCommandException;
use Webmozart\Assert\Assert;

final readonly class ListStoresQuery
{
    private function __construct(
        public ?string $name,
        public ?string $city,
        public ?string $countryIso,
        public string $sortBy,
        public string $sortOrder,
        public int $limit,
        public int $offset,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function validateAndCreate(array $data): self
    {
        $errors = [];

        $allowedSortBy = ['created_at', 'updated_at', 'name', 'city'];
        $allowedSortOrder = ['ASC', 'DESC'];

        $name = isset($data['name']) ? trim((string)$data['name']) : null;
        $city = isset($data['city']) ? trim((string)$data['city']) : null;
        $countryIso = isset($data['country_iso']) ? trim((string)$data['country_iso']) : null;
        $sortBy = (string)($data['sort_by'] ?? 'created_at');
        $sortOrder = strtoupper((string)($data['sort_order'] ?? 'DESC'));
        $limit = (int)($data['limit'] ?? 20);
        $offset = (int)($data['offset'] ?? 0);

        if ($name !== null && $name !== '') {
            try {
                Assert::maxLength($name, 100, '"name" cannot exceed 100 characters.');
            } catch (\InvalidArgumentException $e) {
                $errors['name'] = $e->getMessage();
            }
        }

        if ($city !== null && $city !== '') {
            try {
                Assert::maxLength($city, 100, '"city" cannot exceed 100 characters.');
            } catch (\InvalidArgumentException $e) {
                $errors['city'] = $e->getMessage();
            }
        }

        if ($countryIso !== null && $countryIso !== '') {
            try {
                Assert::regex($countryIso, '/^[A-Za-z]{2}$/', '"country_iso" must be a 2-letter ISO code (e.g. FR).');
            } catch (\InvalidArgumentException $e) {
                $errors['country_iso'] = $e->getMessage();
            }
        }

        try {
            Assert::inArray($sortBy, $allowedSortBy, sprintf('"sort_by" must be one of: %s.', implode(', ', $allowedSortBy)));
        } catch (\InvalidArgumentException $e) {
            $errors['sort_by'] = $e->getMessage();
        }

        try {
            Assert::inArray($sortOrder, $allowedSortOrder, '"sort_order" must be ASC or DESC.');
        } catch (\InvalidArgumentException $e) {
            $errors['sort_order'] = $e->getMessage();
        }

        try {
            Assert::range($limit, 1, 100, '"limit" must be between 1 and 100.');
        } catch (\InvalidArgumentException $e) {
            $errors['limit'] = $e->getMessage();
        }

        try {
            Assert::greaterThanEq($offset, 0, '"offset" must be 0 or greater.');
        } catch (\InvalidArgumentException $e) {
            $errors['offset'] = $e->getMessage();
        }

        if ($errors !== []) {
            throw new InvalidCommandException($errors);
        }

        return new self(
            name: ($name !== null && $name !== '') ? $name : null,
            city: ($city !== null && $city !== '') ? $city : null,
            countryIso: ($countryIso !== null && $countryIso !== '') ? strtoupper($countryIso) : null,
            sortBy: $sortBy,
            sortOrder: $sortOrder,
            limit: $limit,
            offset: $offset,
        );
    }
}
