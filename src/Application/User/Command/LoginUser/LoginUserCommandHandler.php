<?php

declare(strict_types=1);

namespace App\Application\User\Command\LoginUser;

use App\Application\Shared\Logger\LoggerInterface;
use App\Application\User\Repository\UserRepositoryInterface;
use App\Domain\User\Exception\InvalidCredentialsException;
use App\Domain\User\ValueObject\UserEmail;
use App\Infrastructure\Auth\JwtService;

final readonly class LoginUserCommandHandler
{
    public function __construct(
        private UserRepositoryInterface $repository,
        private JwtService $jwtService,
        private LoggerInterface $logger,
    ) {
    }

    public function handle(LoginUserCommand $command): string
    {
        $user = $this->repository->findByEmail(new UserEmail($command->email));

        if ($user === null || !$user->getPassword()->verify($command->plainPassword)) {
            $this->logger->warning('Failed login attempt', [
                'context' => 'auth',
                'email' => $command->email,
            ]);
            throw new InvalidCredentialsException();
        }

        $this->logger->info('User logged in', [
            'context' => 'auth',
            'user_id' => $user->getId()->getValue(),
        ]);

        return $this->jwtService->generate(
            userId: $user->getId()->getValue(),
            role: $user->getRole()->value,
        );
    }
}
