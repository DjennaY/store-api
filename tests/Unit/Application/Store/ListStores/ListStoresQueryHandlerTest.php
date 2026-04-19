<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Store\ListStores;

use App\Application\Store\DTO\StoreDTO;
use App\Application\Store\Query\ListStores\ListStoresQuery;
use App\Application\Store\Query\ListStores\ListStoresQueryHandler;
use App\Application\Store\Repository\StoreRepositoryInterface;
use App\Domain\Store\Entity\Store;
use App\Domain\Store\ValueObject\StoreAddress;
use App\Domain\Store\ValueObject\StoreId;
use App\Domain\Store\ValueObject\StoreName;
use App\Domain\User\ValueObject\UserId;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ListStoresQueryHandlerTest extends TestCase
{
    private MockObject&StoreRepositoryInterface $repository;
    private ListStoresQueryHandler $handler;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(StoreRepositoryInterface::class);
        $this->handler = new ListStoresQueryHandler($this->repository);
    }

    public function testItReturnsEmptyArrayWhenNoStoresFound(): void
    {
        $this->repository->method('findAll')->willReturn([]);

        $result = $this->handler->handle(ListStoresQuery::validateAndCreate([]));

        $this->assertSame([], $result);
    }

    public function testItMapsEntitiesToDtos(): void
    {
        $store = $this->buildStore();
        $this->repository->method('findAll')->willReturn([$store, $store]);

        $result = $this->handler->handle(ListStoresQuery::validateAndCreate([]));

        $this->assertCount(2, $result);
        $this->assertContainsOnlyInstancesOf(StoreDTO::class, $result);
    }

    private function buildStore(): Store
    {
        return Store::reconstitute(
            new StoreId('550e8400-e29b-41d4-a716-446655440000'),
            new StoreName('Test Store'),
            new StoreAddress('1 rue Test', 'Paris', '75001', 'FR'),
            '+33123456789',
            new UserId('550e8400-e29b-41d4-a716-446655440000'),
            new DateTimeImmutable(),
            new DateTimeImmutable(),
            null,
        );
    }

    public function testFiltersArePassedToRepository(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('findAll')
            ->with($this->callback(function ($criteria) {
                return $criteria->name === 'Apple'
                    && $criteria->sortBy === 'name'
                    && $criteria->limit === 10;
            }))
            ->willReturn([]);

        $this->handler->handle(ListStoresQuery::validateAndCreate([
            'name' => 'Apple',
            'sort_by' => 'name',
            'limit' => '10',
        ]));
    }
}
