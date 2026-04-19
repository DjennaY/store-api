<?php

declare(strict_types=1);

namespace App\Application\Store\Command\UpdateStore;

use App\Application\Shared\Logger\LoggerInterface;
use App\Application\Store\Repository\StoreRepositoryInterface;
use App\Domain\Store\Exception\StoreAccessDeniedException;
use App\Domain\Store\Exception\StoreDuplicateException;
use App\Domain\Store\Exception\StoreNotFoundException;
use App\Domain\Store\ValueObject\NaturalKey;
use App\Domain\Store\ValueObject\StoreAddress;
use App\Domain\Store\ValueObject\StoreId;
use App\Domain\Store\ValueObject\StoreName;

final readonly class UpdateStoreCommandHandler
{
    public function __construct(
        private StoreRepositoryInterface $repository,
        private LoggerInterface $logger,
    ) {
    }

    public function handle(UpdateStoreCommand $command): void
    {
        $storeId = new StoreId($command->id);
        $store = $this->repository->findById($storeId);

        if ($store === null || $store->isDeleted()) {
            throw new StoreNotFoundException($command->id);
        }

        if (!$command->isAdmin && $store->getCreatedBy()->getValue() !== $command->requestedBy) {
            throw new StoreAccessDeniedException($command->id);
        }

        $naturalKey = new NaturalKey(
            $command->name,
            $command->address,
            $command->city,
            $command->zipCode,
            $command->countryIso,
        );

        $existing = $this->repository->findByNaturalKey($naturalKey);
        if ($existing !== null && $existing->getId()->getValue() !== $command->id) {
            $this->logger->warning('Store duplicate detected on update', [
                'context' => 'store',
                'natural_key' => $naturalKey->getValue(),
                'existing_id' => $existing->getId()->getValue(),
            ]);
            throw new StoreDuplicateException($existing->getId()->getValue());
        }

        $store->update(
            name: new StoreName($command->name),
            address: new StoreAddress($command->address, $command->city, $command->zipCode, $command->countryIso),
            phone: $command->phone,
        );

        $this->repository->save($store);

        $this->logger->info('Store updated', [
            'context' => 'store',
            'store_id' => $command->id,
        ]);
    }
}
