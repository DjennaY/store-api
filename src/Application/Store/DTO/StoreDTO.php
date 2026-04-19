<?php

declare(strict_types=1);

namespace App\Application\Store\DTO;

use App\Domain\Store\Entity\Store;

final readonly class StoreDTO
{
    public function __construct(
        public string $id,
        public string $name,
        public string $address,
        public string $city,
        public string $zipCode,
        public string $countryIso,
        public string $phone,
        public string $createdBy,
        public string $createdAt,
        public string $updatedAt,
    ) {
    }

    public static function fromEntity(Store $store): self
    {
        return new self(
            id: $store->getId()->getValue(),
            name: $store->getName()->getValue(),
            address: $store->getAddress()->getStreet(),
            city: $store->getAddress()->getCity(),
            zipCode: $store->getAddress()->getZipCode(),
            countryIso: $store->getAddress()->getCountryIso(),
            phone: $store->getPhone(),
            createdBy: $store->getCreatedBy()->getValue(),
            createdAt: $store->getCreatedAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $store->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
