<?php

declare(strict_types=1);

namespace App\Application\Store\Command\DeleteStore;

use App\Application\Shared\Logger\LoggerInterface;
use App\Application\Store\Repository\StoreRepositoryInterface;
use App\Domain\Store\Exception\StoreAccessDeniedException;
use App\Domain\Store\Exception\StoreNotFoundException;
use App\Domain\Store\ValueObject\StoreId;

final readonly class DeleteStoreCommandHandler
{
    public function __construct(
        private StoreRepositoryInterface $repository,
        private LoggerInterface $logger,
    ) {
    }

    public function handle(DeleteStoreCommand $command): void
    {
        $storeId = new StoreId($command->id);
        $store = $this->repository->findById($storeId);

        if ($store === null || $store->isDeleted()) {
            throw new StoreNotFoundException($command->id);
        }

        if (!$command->isAdmin && $store->getCreatedBy()->getValue() !== $command->requestedBy) {
            throw new StoreAccessDeniedException($command->id);
        }

        $this->repository->softDelete($storeId);

        $this->logger->info('Store soft-deleted', [
            'context' => 'store',
            'store_id' => $command->id,
        ]);
    }
}
