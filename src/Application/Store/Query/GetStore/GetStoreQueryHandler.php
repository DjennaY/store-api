<?php

declare(strict_types=1);

namespace App\Application\Store\Query\GetStore;

use App\Application\Store\DTO\StoreDTO;
use App\Application\Store\Repository\StoreRepositoryInterface;
use App\Domain\Store\Exception\StoreNotFoundException;
use App\Domain\Store\ValueObject\StoreId;

final readonly class GetStoreQueryHandler
{
    public function __construct(private StoreRepositoryInterface $repository)
    {
    }

    public function handle(GetStoreQuery $query): StoreDTO
    {
        $store = $this->repository->findById(new StoreId($query->id));

        if ($store === null || $store->isDeleted()) {
            throw new StoreNotFoundException($query->id);
        }

        return StoreDTO::fromEntity($store);
    }
}
