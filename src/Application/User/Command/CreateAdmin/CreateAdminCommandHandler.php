<?php

declare(strict_types=1);

namespace App\Application\User\Command\CreateAdmin;

use App\Application\Shared\Logger\LoggerInterface;
use App\Application\User\Repository\UserRepositoryInterface;
use App\Domain\User\Entity\User;
use App\Domain\User\Exception\AdminUserConflictException;
use App\Domain\User\ValueObject\HashedPassword;
use App\Domain\User\ValueObject\UserEmail;
use App\Domain\User\ValueObject\UserId;
use Ramsey\Uuid\Uuid;

final readonly class CreateAdminCommandHandler
{
    public function __construct(
        private UserRepositoryInterface $repository,
        private LoggerInterface $logger,
    ) {
    }

    public function handle(CreateAdminCommand $command): string
    {
        $email = new UserEmail($command->email);
        $existing = $this->repository->findByEmail($email);

        if ($existing !== null) {
            if ($existing->getRole()->isAdmin()) {
                $this->logger->info('Create-admin skipped: admin already exists', [
                    'context' => 'cli',
                    'email' => $command->email,
                    'user_id' => $existing->getId()->getValue(),
                ]);
                return $existing->getId()->getValue();
            }

            throw new AdminUserConflictException($command->email);
        }

        $user = User::createAdmin(
            id: new UserId(Uuid::uuid4()->toString()),
            email: $email,
            firstName: $command->firstName,
            lastName: $command->lastName,
            password: HashedPassword::fromPlainText($command->plainPassword),
        );

        $this->repository->save($user);

        $this->logger->info('Admin user created', [
            'context' => 'cli',
            'user_id' => $user->getId()->getValue(),
        ]);

        return $user->getId()->getValue();
    }
}
