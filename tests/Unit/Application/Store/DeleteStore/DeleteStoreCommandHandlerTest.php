<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Store\DeleteStore;

use App\Application\Shared\Logger\LoggerInterface;
use App\Application\Store\Command\DeleteStore\DeleteStoreCommand;
use App\Application\Store\Command\DeleteStore\DeleteStoreCommandHandler;
use App\Application\Store\Repository\StoreRepositoryInterface;
use App\Domain\Store\Entity\Store;
use App\Domain\Store\Exception\StoreAccessDeniedException;
use App\Domain\Store\Exception\StoreNotFoundException;
use App\Domain\Store\ValueObject\StoreAddress;
use App\Domain\Store\ValueObject\StoreId;
use App\Domain\Store\ValueObject\StoreName;
use App\Domain\User\ValueObject\UserId;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class DeleteStoreCommandHandlerTest extends TestCase
{
    private MockObject&StoreRepositoryInterface $repository;
    private DeleteStoreCommandHandler $handler;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(StoreRepositoryInterface::class);
        $this->handler = new DeleteStoreCommandHandler($this->repository, $this->createMock(LoggerInterface::class));
    }

    private function command(bool $isAdmin = false, string $requestedBy = '550e8400-e29b-41d4-a716-446655440000'): DeleteStoreCommand
    {
        return DeleteStoreCommand::validateAndCreate([
            'id' => '550e8400-e29b-41d4-a716-446655440000',
            'requested_by' => $requestedBy,
            'is_admin' => $isAdmin,
        ]);
    }

    public function testItSoftDeletesStore(): void
    {
        $this->repository->method('findById')->willReturn($this->buildStore());
        $this->repository->expects($this->once())->method('softDelete');

        $this->handler->handle($this->command());
    }

    public function testItThrowsWhenUserIsNotOwner(): void
    {
        $this->repository->method('findById')->willReturn($this->buildStore());
        $this->expectException(StoreAccessDeniedException::class);
        $this->handler->handle($this->command(isAdmin: false, requestedBy: '550e8400-e29b-41d4-a716-999999999999'));
    }

    public function testItAllowsDeleteWhenAdmin(): void
    {
        $this->repository->method('findById')->willReturn($this->buildStore());
        $this->repository->expects($this->once())->method('softDelete');
        $this->handler->handle($this->command(isAdmin: true, requestedBy: '550e8400-e29b-41d4-a716-999999999999'));
    }

    public function testItThrowsWhenStoreNotFound(): void
    {
        $this->repository->method('findById')->willReturn(null);
        $this->expectException(StoreNotFoundException::class);
        $this->handler->handle($this->command());
    }

    public function testItThrowsWhenStoreAlreadyDeleted(): void
    {
        $this->repository->method('findById')->willReturn($this->buildStore(deleted: true));
        $this->expectException(StoreNotFoundException::class);
        $this->handler->handle($this->command());
    }

    private function buildStore(bool $deleted = false): Store
    {
        return Store::reconstitute(
            new StoreId('550e8400-e29b-41d4-a716-446655440000'),
            new StoreName('Test Store'),
            new StoreAddress('1 rue Test', 'Paris', '75001', 'FR'),
            '+33123456789',
            new UserId('550e8400-e29b-41d4-a716-446655440000'),
            new DateTimeImmutable(),
            new DateTimeImmutable(),
            $deleted ? new DateTimeImmutable() : null,
        );
    }
}
