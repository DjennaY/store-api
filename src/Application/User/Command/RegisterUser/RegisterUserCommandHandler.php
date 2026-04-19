<?php

declare(strict_types=1);

namespace App\Application\User\Command\RegisterUser;

use App\Application\Shared\Logger\LoggerInterface;
use App\Application\User\Repository\UserRepositoryInterface;
use App\Domain\User\Entity\User;
use App\Domain\User\Exception\UserAlreadyExistsException;
use App\Domain\User\ValueObject\HashedPassword;
use App\Domain\User\ValueObject\UserEmail;
use App\Domain\User\ValueObject\UserId;
use Ramsey\Uuid\Uuid;

final readonly class RegisterUserCommandHandler
{
    public function __construct(
        private UserRepositoryInterface $repository,
        private LoggerInterface $logger,
    ) {
    }

    public function handle(RegisterUserCommand $command): string
    {
        $email = new UserEmail($command->email);

        if ($this->repository->existsByEmail($email)) {
            $this->logger->warning('Registration attempt with existing email', [
                'context' => 'auth',
                'email' => $command->email,
            ]);
            throw new UserAlreadyExistsException($command->email);
        }

        $user = User::register(
            id: new UserId(Uuid::uuid4()->toString()),
            email: $email,
            firstName: $command->firstName,
            lastName: $command->lastName,
            password: HashedPassword::fromPlainText($command->plainPassword),
        );

        $this->repository->save($user);

        $this->logger->info('User registered', [
            'context' => 'auth',
            'user_id' => $user->getId()->getValue(),
        ]);

        return $user->getId()->getValue();
    }
}
