<?php

declare(strict_types=1);

namespace App\Application\Store\Repository;

use App\Domain\Store\Criteria\StoreCriteria;
use App\Domain\Store\Entity\Store;
use App\Domain\Store\ValueObject\NaturalKey;
use App\Domain\Store\ValueObject\StoreId;

interface StoreRepositoryInterface
{
    public function findById(StoreId $id): ?Store;

    public function findByNaturalKey(NaturalKey $key): ?Store;

    /** @return Store[] */
    public function findAll(StoreCriteria $criteria): array;

    public function save(Store $store): void;

    public function softDelete(StoreId $id): void;
}
