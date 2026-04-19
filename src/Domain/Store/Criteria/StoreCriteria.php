<?php

declare(strict_types=1);

namespace App\Domain\Store\Criteria;

final readonly class StoreCriteria
{
    public function __construct(
        public ?string $name = null,
        public ?string $city = null,
        public ?string $countryIso = null,
        public string $sortBy = 'created_at',
        public string $sortOrder = 'DESC',
        public int $limit = 20,
        public int $offset = 0,
    ) {
    }
}
