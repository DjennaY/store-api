<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Store\UpdateStore;

use App\Application\Shared\Logger\LoggerInterface;
use App\Application\Store\Command\UpdateStore\UpdateStoreCommand;
use App\Application\Store\Command\UpdateStore\UpdateStoreCommandHandler;
use App\Application\Store\Repository\StoreRepositoryInterface;
use App\Domain\Store\Entity\Store;
use App\Domain\Store\Exception\StoreAccessDeniedException;
use App\Domain\Store\Exception\StoreDuplicateException;
use App\Domain\Store\Exception\StoreNotFoundException;
use App\Domain\Store\ValueObject\StoreAddress;
use App\Domain\Store\ValueObject\StoreId;
use App\Domain\Store\ValueObject\StoreName;
use App\Domain\User\ValueObject\UserId;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class UpdateStoreCommandHandlerTest extends TestCase
{
    private MockObject&StoreRepositoryInterface $repository;
    private UpdateStoreCommandHandler $handler;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(StoreRepositoryInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $this->handler = new UpdateStoreCommandHandler($this->repository, $logger);
    }

    private function validCommand(bool $isAdmin = false, string $requestedBy = '550e8400-e29b-41d4-a716-446655440000'): UpdateStoreCommand
    {
        return UpdateStoreCommand::validateAndCreate([
            'id' => '550e8400-e29b-41d4-a716-446655440000',
            'name' => 'Updated',
            'address' => '1 rue',
            'city' => 'Paris',
            'zip_code' => '75001',
            'country_iso' => 'FR',
            'phone' => '+33123456789',
            'requested_by' => $requestedBy,
            'is_admin' => $isAdmin,
        ]);
    }

    public function testItThrowsStoreNotFoundWhenStoreDoesNotExist(): void
    {
        $this->repository->method('findById')->willReturn(null);
        $this->expectException(StoreNotFoundException::class);
        $this->handler->handle($this->validCommand());
    }

    public function testItThrowsWhenStoreIsSoftDeleted(): void
    {
        $this->repository->method('findById')->willReturn($this->buildStore(deleted: true));
        $this->expectException(StoreNotFoundException::class);
        $this->handler->handle($this->validCommand());
    }

    public function testItThrowsWhenUserIsNotOwner(): void
    {
        $store = $this->buildStore(createdBy: '550e8400-e29b-41d4-a716-446655440000');
        $this->repository->method('findById')->willReturn($store);
        $this->expectException(StoreAccessDeniedException::class);
        $this->handler->handle($this->validCommand(isAdmin: false, requestedBy: '550e8400-e29b-41d4-a716-999999999999'));
    }

    public function testItAllowsUpdateWhenAdmin(): void
    {
        $store = $this->buildStore(createdBy: '550e8400-e29b-41d4-a716-446655440000');
        $this->repository->method('findById')->willReturn($store);
        $this->repository->method('findByNaturalKey')->willReturn(null);
        $this->repository->expects($this->once())->method('save');
        $this->handler->handle($this->validCommand(isAdmin: true, requestedBy: '550e8400-e29b-41d4-a716-999999999999'));
    }

    public function testItThrowsOnDuplicateNaturalKey(): void
    {
        $store = $this->buildStore('550e8400-e29b-41d4-a716-446655440000');
        $this->repository->method('findById')->willReturn($store);
        $this->repository->method('findByNaturalKey')->willReturn($this->buildStore('550e8400-e29b-41d4-a716-446655440099'));

        $this->expectException(StoreDuplicateException::class);
        $this->handler->handle($this->validCommand());
    }

    public function testItAllowsUpdateWhenNaturalKeyBelongsToSameStore(): void
    {
        $store = $this->buildStore('550e8400-e29b-41d4-a716-446655440000');
        $this->repository->method('findById')->willReturn($store);
        $this->repository->method('findByNaturalKey')->willReturn($store);
        $this->repository->expects($this->once())->method('save');

        $this->handler->handle($this->validCommand());
    }

    private function buildStore(
        string $id = '550e8400-e29b-41d4-a716-446655440000',
        bool $deleted = false,
        string $createdBy = '550e8400-e29b-41d4-a716-446655440000',
    ): Store {
        return Store::reconstitute(
            new StoreId($id),
            new StoreName('Test Store'),
            new StoreAddress('1 rue Test', 'Paris', '75001', 'FR'),
            '+33123456789',
            new UserId($createdBy),
            new DateTimeImmutable(),
            new DateTimeImmutable(),
            $deleted ? new DateTimeImmutable() : null,
        );
    }
}
