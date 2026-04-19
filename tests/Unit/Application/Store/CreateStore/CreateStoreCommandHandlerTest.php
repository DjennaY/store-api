<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Store\CreateStore;

use App\Application\Shared\Logger\LoggerInterface;
use App\Application\Store\Command\CreateStore\CreateStoreCommand;
use App\Application\Store\Command\CreateStore\CreateStoreCommandHandler;
use App\Application\Store\Repository\StoreRepositoryInterface;
use App\Domain\Store\Entity\Store;
use App\Domain\Store\Exception\StoreDuplicateException;
use App\Domain\Store\ValueObject\StoreAddress;
use App\Domain\Store\ValueObject\StoreId;
use App\Domain\Store\ValueObject\StoreName;
use App\Domain\User\ValueObject\UserId;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CreateStoreCommandHandlerTest extends TestCase
{
    private MockObject&StoreRepositoryInterface $repository;
    private MockObject&LoggerInterface $logger;
    private CreateStoreCommandHandler $handler;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(StoreRepositoryInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->handler = new CreateStoreCommandHandler($this->repository, $this->logger);
    }

    private function validCommand(): CreateStoreCommand
    {
        return CreateStoreCommand::validateAndCreate([
            'name' => 'Test Store', 'address' => '1 rue Test', 'city' => 'Paris',
            'zip_code' => '75001', 'country_iso' => 'FR',
            'phone' => '+33123456789', 'created_by' => '550e8400-e29b-41d4-a716-446655440000',
        ]);
    }

    public function testItCreatesStoreAndReturnsUuid(): void
    {
        $this->repository->method('findByNaturalKey')->willReturn(null);
        $this->repository->expects($this->once())->method('save');

        $id = $this->handler->handle($this->validCommand());

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $id
        );
    }

    public function testItThrowsStoreDuplicateExceptionWhenNaturalKeyExists(): void
    {
        $this->repository->method('findByNaturalKey')->willReturn($this->buildStore('550e8400-e29b-41d4-a716-446655440001'));
        $this->repository->expects($this->never())->method('save');
        $this->expectException(StoreDuplicateException::class);
        $this->handler->handle($this->validCommand());
    }

    public function testDuplicateExceptionContainsExistingId(): void
    {
        $existingId = '550e8400-e29b-41d4-a716-446655440001';
        $this->repository->method('findByNaturalKey')->willReturn($this->buildStore($existingId));

        $caught = null;
        try {
            $this->handler->handle($this->validCommand());
        } catch (StoreDuplicateException $e) {
            $caught = $e;
        }
        $this->assertSame($existingId, $caught->getExistingId());
    }

    private function buildStore(string $id = '550e8400-e29b-41d4-a716-446655440000', bool $deleted = false): Store
    {
        return Store::reconstitute(
            new StoreId($id),
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
