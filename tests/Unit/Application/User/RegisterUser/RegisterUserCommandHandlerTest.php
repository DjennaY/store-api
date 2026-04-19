<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\User\RegisterUser;

use App\Application\Shared\Logger\LoggerInterface;
use App\Application\User\Command\RegisterUser\RegisterUserCommand;
use App\Application\User\Command\RegisterUser\RegisterUserCommandHandler;
use App\Application\User\Repository\UserRepositoryInterface;
use App\Domain\User\Exception\UserAlreadyExistsException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class RegisterUserCommandHandlerTest extends TestCase
{
    private MockObject&UserRepositoryInterface $repository;
    private RegisterUserCommandHandler $handler;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(UserRepositoryInterface::class);
        $this->handler = new RegisterUserCommandHandler($this->repository, $this->createMock(LoggerInterface::class));
    }

    private function command(): RegisterUserCommand
    {
        return RegisterUserCommand::validateAndCreate([
            'first_name' => 'Jean', 'last_name' => 'Dupont',
            'email' => 'jean@example.com', 'password' => 'securepass123',
        ]);
    }

    public function testItRegistersUserAndReturnsUuid(): void
    {
        $this->repository->method('existsByEmail')->willReturn(false);
        $this->repository->expects($this->once())->method('save');

        $id = $this->handler->handle($this->command());

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $id
        );
    }

    public function testItThrowsWhenEmailAlreadyExists(): void
    {
        $this->repository->method('existsByEmail')->willReturn(true);
        $this->repository->expects($this->never())->method('save');
        $this->expectException(UserAlreadyExistsException::class);
        $this->handler->handle($this->command());
    }
}
