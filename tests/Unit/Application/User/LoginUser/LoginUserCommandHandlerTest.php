<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\User\LoginUser;

use App\Application\Shared\Logger\LoggerInterface;
use App\Application\User\Command\LoginUser\LoginUserCommand;
use App\Application\User\Command\LoginUser\LoginUserCommandHandler;
use App\Application\User\Repository\UserRepositoryInterface;
use App\Domain\User\Entity\User;
use App\Domain\User\Exception\InvalidCredentialsException;
use App\Domain\User\ValueObject\HashedPassword;
use App\Domain\User\ValueObject\UserEmail;
use App\Domain\User\ValueObject\UserId;
use App\Domain\User\ValueObject\UserRole;
use App\Infrastructure\Auth\JwtService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class LoginUserCommandHandlerTest extends TestCase
{
    private MockObject&UserRepositoryInterface $repository;
    private LoginUserCommandHandler $handler;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(UserRepositoryInterface::class);
        $this->handler = new LoginUserCommandHandler(
            $this->repository,
            new JwtService('test-secret-key-long-enough-for-hs256-algorithm'),
            $this->createMock(LoggerInterface::class)
        );
    }

    private function buildUser(string $plainPassword): User
    {
        return User::reconstitute(
            id: new UserId('550e8400-e29b-41d4-a716-446655440000'),
            email: new UserEmail('jean@example.com'),
            firstName: 'Jean',
            lastName: 'Dupont',
            password: HashedPassword::fromPlainText($plainPassword),
            role: UserRole::USER,
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable(),
        );
    }

    public function testItReturnsTokenOnSuccess(): void
    {
        $this->repository->method('findByEmail')->willReturn($this->buildUser('securepass123'));

        $token = $this->handler->handle(
            LoginUserCommand::validateAndCreate(['email' => 'jean@example.com', 'password' => 'securepass123'])
        );

        $this->assertMatchesRegularExpression('/^[^.]+\.[^.]+\.[^.]+$/', $token);
    }

    public function testItThrowsWhenUserNotFound(): void
    {
        $this->repository->method('findByEmail')->willReturn(null);
        $this->expectException(InvalidCredentialsException::class);

        $this->handler->handle(
            LoginUserCommand::validateAndCreate(['email' => 'jean@example.com', 'password' => 'secret'])
        );
    }

    public function testItThrowsOnWrongPassword(): void
    {
        $this->repository->method('findByEmail')->willReturn($this->buildUser('correctpassword'));
        $this->expectException(InvalidCredentialsException::class);

        $this->handler->handle(
            LoginUserCommand::validateAndCreate(['email' => 'jean@example.com', 'password' => 'wrongpassword'])
        );
    }
}
