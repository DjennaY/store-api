<?php

declare(strict_types=1);

namespace App\Application\Store\Query\ListStores;

use App\Application\Store\DTO\StoreDTO;
use App\Application\Store\Repository\StoreRepositoryInterface;
use App\Domain\Store\Criteria\StoreCriteria;

final readonly class ListStoresQueryHandler
{
    public function __construct(private StoreRepositoryInterface $repository)
    {
    }

    /** @return StoreDTO[] */
    public function handle(ListStoresQuery $query): array
    {
        return array_map(
            fn ($store) => StoreDTO::fromEntity($store),
            $this->repository->findAll(new StoreCriteria(
                name: $query->name,
                city: $query->city,
                countryIso: $query->countryIso,
                sortBy: $query->sortBy,
                sortOrder: $query->sortOrder,
                limit: $query->limit,
                offset: $query->offset,
            ))
        );
    }
}
