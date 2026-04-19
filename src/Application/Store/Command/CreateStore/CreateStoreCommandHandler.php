<?php

declare(strict_types=1);

namespace App\Application\Store\Command\CreateStore;

use App\Application\Shared\Logger\LoggerInterface;
use App\Application\Store\Repository\StoreRepositoryInterface;
use App\Domain\Store\Entity\Store;
use App\Domain\Store\Exception\StoreDuplicateException;
use App\Domain\Store\ValueObject\NaturalKey;
use App\Domain\Store\ValueObject\StoreAddress;
use App\Domain\Store\ValueObject\StoreId;
use App\Domain\Store\ValueObject\StoreName;
use App\Domain\User\ValueObject\UserId;
use Ramsey\Uuid\Uuid;

final readonly class CreateStoreCommandHandler
{
    public function __construct(
        private StoreRepositoryInterface $repository,
        private LoggerInterface $logger,
    ) {
    }

    public function handle(CreateStoreCommand $command): string
    {
        $naturalKey = new NaturalKey(
            $command->name,
            $command->address,
            $command->city,
            $command->zipCode,
            $command->countryIso,
        );

        $existing = $this->repository->findByNaturalKey($naturalKey);
        if ($existing !== null) {
            $this->logger->warning('Store duplicate detected on create', [
                'context' => 'store',
                'natural_key' => $naturalKey->getValue(),
                'existing_id' => $existing->getId()->getValue(),
            ]);
            throw new StoreDuplicateException($existing->getId()->getValue());
        }

        $store = Store::create(
            id: new StoreId(Uuid::uuid4()->toString()),
            name: new StoreName($command->name),
            address: new StoreAddress($command->address, $command->city, $command->zipCode, $command->countryIso),
            phone: $command->phone,
            createdBy: new UserId($command->createdBy),
        );

        $this->repository->save($store);

        $this->logger->info('Store created', [
            'context' => 'store',
            'store_id' => $store->getId()->getValue(),
            'user_id' => $command->createdBy,
        ]);

        return $store->getId()->getValue();
    }
}
