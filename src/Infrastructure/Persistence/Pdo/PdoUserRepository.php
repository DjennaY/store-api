<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Pdo;

use App\Application\User\Repository\UserRepositoryInterface;
use App\Domain\User\Entity\User;
use App\Domain\User\Exception\CouldNotFetchUserException;
use App\Domain\User\Exception\CouldNotSaveUserException;
use App\Domain\User\ValueObject\HashedPassword;
use App\Domain\User\ValueObject\UserEmail;
use App\Domain\User\ValueObject\UserId;
use App\Domain\User\ValueObject\UserRole;

final class PdoUserRepository implements UserRepositoryInterface
{
    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function findById(UserId $id): ?User
    {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = :id');
            $stmt->execute(['id' => $id->getValue()]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $row !== false ? $this->hydrate($row) : null;
        } catch (\Throwable $e) {
            throw new CouldNotFetchUserException('findById', $e);
        }
    }

    public function findByEmail(UserEmail $email): ?User
    {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = :email');
            $stmt->execute(['email' => $email->getValue()]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $row !== false ? $this->hydrate($row) : null;
        } catch (\Throwable $e) {
            throw new CouldNotFetchUserException('findByEmail', $e);
        }
    }

    public function save(User $user): void
    {
        try {
            $stmt = $this->pdo->prepare('
                INSERT INTO users (id, email, first_name, last_name, password, role, created_at, updated_at)
                VALUES (:id, :email, :first_name, :last_name, :password, :role, :created_at, :updated_at)
                ON DUPLICATE KEY UPDATE
                    email      = VALUES(email),
                    first_name = VALUES(first_name),
                    last_name  = VALUES(last_name),
                    password   = VALUES(password),
                    role       = VALUES(role),
                    updated_at = VALUES(updated_at)
            ');
            $stmt->execute([
                'id' => $user->getId()->getValue(),
                'email' => $user->getEmail()->getValue(),
                'first_name' => $user->getFirstName(),
                'last_name' => $user->getLastName(),
                'password' => $user->getPassword()->getHash(),
                'role' => $user->getRole()->value,
                'created_at' => $user->getCreatedAt()->format('Y-m-d H:i:s'),
                'updated_at' => $user->getUpdatedAt()->format('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            throw new CouldNotSaveUserException('save', $e);
        }
    }

    public function existsByEmail(UserEmail $email): bool
    {
        try {
            $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM users WHERE email = :email');
            $stmt->execute(['email' => $email->getValue()]);
            return (int)$stmt->fetchColumn() > 0;
        } catch (\Throwable $e) {
            throw new CouldNotFetchUserException('existsByEmail', $e);
        }
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): User
    {
        return User::reconstitute(
            id: new UserId($row['id']),
            email: new UserEmail($row['email']),
            firstName: $row['first_name'],
            lastName: $row['last_name'],
            password: HashedPassword::fromHash($row['password']),
            role: UserRole::from($row['role']),
            createdAt: new \DateTimeImmutable($row['created_at']),
            updatedAt: new \DateTimeImmutable($row['updated_at']),
        );
    }
}
