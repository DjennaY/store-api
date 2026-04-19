<?php

declare(strict_types=1);

namespace App\Domain\Store\Entity;

use App\Domain\Store\ValueObject\NaturalKey;
use App\Domain\Store\ValueObject\StoreAddress;
use App\Domain\Store\ValueObject\StoreId;
use App\Domain\Store\ValueObject\StoreName;
use App\Domain\User\ValueObject\UserId;
use DateTimeImmutable;

final class Store
{
    private function __construct(
        private readonly StoreId $id,
        private StoreName $name,
        private StoreAddress $address,
        private string $phone,
        private readonly UserId $createdBy,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
        private ?DateTimeImmutable $deletedAt,
    ) {
    }

    public static function create(
        StoreId $id,
        StoreName $name,
        StoreAddress $address,
        string $phone,
        UserId $createdBy,
    ): self {
        $now = new DateTimeImmutable();
        return new self($id, $name, $address, $phone, $createdBy, $now, $now, null);
    }

    public static function reconstitute(
        StoreId $id,
        StoreName $name,
        StoreAddress $address,
        string $phone,
        UserId $createdBy,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
        ?DateTimeImmutable $deletedAt,
    ): self {
        return new self($id, $name, $address, $phone, $createdBy, $createdAt, $updatedAt, $deletedAt);
    }

    public function update(StoreName $name, StoreAddress $address, string $phone): void
    {
        $this->name = $name;
        $this->address = $address;
        $this->phone = $phone;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function softDelete(): void
    {
        $this->deletedAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    public function getNaturalKey(): NaturalKey
    {
        return new NaturalKey(
            $this->name->getValue(),
            $this->address->getStreet(),
            $this->address->getCity(),
            $this->address->getZipCode(),
            $this->address->getCountryIso(),
        );
    }

    public function getId(): StoreId
    {
        return $this->id;
    }

    public function getName(): StoreName
    {
        return $this->name;
    }

    public function getAddress(): StoreAddress
    {
        return $this->address;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function getCreatedBy(): UserId
    {
        return $this->createdBy;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getDeletedAt(): ?DateTimeImmutable
    {
        return $this->deletedAt;
    }
}
