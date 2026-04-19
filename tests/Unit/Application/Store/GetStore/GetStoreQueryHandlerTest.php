<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Store\GetStore;

use App\Application\Store\Query\GetStore\GetStoreQuery;
use App\Application\Store\Query\GetStore\GetStoreQueryHandler;
use App\Application\Store\Repository\StoreRepositoryInterface;
use App\Domain\Store\Entity\Store;
use App\Domain\Store\Exception\StoreNotFoundException;
use App\Domain\Store\ValueObject\StoreAddress;
use App\Domain\Store\ValueObject\StoreId;
use App\Domain\Store\ValueObject\StoreName;
use App\Domain\User\ValueObject\UserId;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class GetStoreQueryHandlerTest extends TestCase
{
    private MockObject&StoreRepositoryInterface $repository;
    private GetStoreQueryHandler $handler;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(StoreRepositoryInterface::class);
        $this->handler = new GetStoreQueryHandler($this->repository);
    }

    private function query(): GetStoreQuery
    {
        return GetStoreQuery::validateAndCreate(['id' => '550e8400-e29b-41d4-a716-446655440000']);
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

    public function testItReturnsDtoWithExpectedValues(): void
    {
        $this->repository->method('findById')->willReturn($this->buildStore());

        $result = $this->handler->handle($this->query());

        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', $result->id);
        $this->assertSame('Test Store', $result->name);
        $this->assertSame('1 rue Test', $result->address);
        $this->assertSame('Paris', $result->city);
        $this->assertSame('75001', $result->zipCode);
        $this->assertSame('FR', $result->countryIso);
        $this->assertSame('+33123456789', $result->phone);
        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', $result->createdBy);
    }

    public function testItThrowsWhenStoreNotFound(): void
    {
        $this->repository->method('findById')->willReturn(null);
        $this->expectException(StoreNotFoundException::class);
        $this->handler->handle($this->query());
    }

    public function testItThrowsWhenStoreIsSoftDeleted(): void
    {
        $this->repository->method('findById')->willReturn($this->buildStore(deleted: true));

        $this->expectException(StoreNotFoundException::class);
        $this->handler->handle($this->query());
    }
}
