<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\User\CreateAdmin;

use App\Application\Shared\Logger\LoggerInterface;
use App\Application\User\Command\CreateAdmin\CreateAdminCommand;
use App\Application\User\Command\CreateAdmin\CreateAdminCommandHandler;
use App\Application\User\Repository\UserRepositoryInterface;
use App\Domain\User\Entity\User;
use App\Domain\User\Exception\AdminUserConflictException;
use App\Domain\User\ValueObject\HashedPassword;
use App\Domain\User\ValueObject\UserEmail;
use App\Domain\User\ValueObject\UserId;
use App\Domain\User\ValueObject\UserRole;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CreateAdminCommandHandlerTest extends TestCase
{
    private MockObject&UserRepositoryInterface $repository;
    private CreateAdminCommandHandler $handler;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(UserRepositoryInterface::class);
        $this->handler = new CreateAdminCommandHandler(
            $this->repository,
            $this->createMock(LoggerInterface::class),
        );
    }

    private function command(): CreateAdminCommand
    {
        return CreateAdminCommand::validateAndCreate([
            'email' => 'admin@example.com',
            'password' => 'securepass123',
            'first_name' => 'Admin',
            'last_name' => 'User',
        ]);
    }

    private function buildUser(string $id, UserRole $role): User
    {
        return User::reconstitute(
            id: new UserId($id),
            email: new UserEmail('admin@example.com'),
            firstName: 'Admin',
            lastName: 'User',
            password: HashedPassword::fromPlainText('securepass123'),
            role: $role,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable(),
        );
    }

    public function testItCreatesAdminAndReturnsUuid(): void
    {
        $this->repository->method('findByEmail')->willReturn(null);
        $this->repository->expects($this->once())->method('save');

        $id = $this->handler->handle($this->command());

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $id,
        );
    }

    public function testItIsIdempotentWhenAdminAlreadyExists(): void
    {
        $existingId = '550e8400-e29b-41d4-a716-446655440000';
        $this->repository->method('findByEmail')->willReturn($this->buildUser($existingId, UserRole::ADMIN));
        $this->repository->expects($this->never())->method('save');

        $id = $this->handler->handle($this->command());

        $this->assertSame($existingId, $id);
    }

    public function testItThrowsWhenEmailBelongsToRegularUser(): void
    {
        $this->repository->method('findByEmail')->willReturn(
            $this->buildUser('550e8400-e29b-41d4-a716-446655440000', UserRole::USER),
        );
        $this->repository->expects($this->never())->method('save');
        $this->expectException(AdminUserConflictException::class);

        $this->handler->handle($this->command());
    }
}
