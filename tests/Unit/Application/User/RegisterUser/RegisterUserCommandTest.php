<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\User\RegisterUser;

use App\Application\Shared\Exception\InvalidCommandException;
use App\Application\User\Command\RegisterUser\RegisterUserCommand;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RegisterUserCommandTest extends TestCase
{
    /** @return array<string, mixed> */
    private static function base(): array
    {
        return [
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'email' => 'jean@example.com',
            'password' => 'securepass123',
        ];
    }

    public function testValidDataReturnsCommand(): void
    {
        $cmd = RegisterUserCommand::validateAndCreate(self::base());
        $this->assertSame('Jean', $cmd->firstName);
        $this->assertSame('jean@example.com', $cmd->email);
    }

    public function testEmailIsNormalizedToLowercase(): void
    {
        $cmd = RegisterUserCommand::validateAndCreate([...self::base(), 'email' => 'JEAN@EXAMPLE.COM']);
        $this->assertSame('jean@example.com', $cmd->email);
    }

    public function testAllRequiredFieldsMissingContainsAllErrors(): void
    {
        $caught = null;
        try {
            RegisterUserCommand::validateAndCreate([]);
        } catch (InvalidCommandException $e) {
            $caught = $e;
        }
        $this->assertInstanceOf(InvalidCommandException::class, $caught);
        foreach (['first_name', 'last_name', 'email', 'password'] as $field) {
            $this->assertArrayHasKey($field, $caught->getErrors(), "Missing error for: {$field}");
        }
    }

    /** @return array<string, array{array<string, mixed>, string}> */
    public static function invalidFieldsProvider(): array
    {
        return [
            // first_name — stringNotEmpty
            'first_name empty' => [[...self::base(), 'first_name' => ''], 'first_name'],

            // last_name — stringNotEmpty
            'last_name empty' => [[...self::base(), 'last_name' => ''], 'last_name'],

            // email — Assert::email (valid RFC format required)
            'email empty' => [[...self::base(), 'email' => ''], 'email'],
            'email no at sign' => [[...self::base(), 'email' => 'jeanexample.com'], 'email'],
            'email no domain' => [[...self::base(), 'email' => 'jean@'], 'email'],
            'email no local part' => [[...self::base(), 'email' => '@example.com'], 'email'],
            'email plain string' => [[...self::base(), 'email' => 'notanemail'], 'email'],

            // password — minLength(8)
            'password empty' => [[...self::base(), 'password' => ''], 'password'],
            'password 1 char' => [[...self::base(), 'password' => 'a'], 'password'],
            'password 7 chars boundary' => [[...self::base(), 'password' => 'pass123'], 'password'],
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    #[DataProvider('invalidFieldsProvider')]
    public function testInvalidFieldThrowsWithError(array $data, string $expectedField): void
    {
        $caught = null;
        try {
            RegisterUserCommand::validateAndCreate($data);
        } catch (InvalidCommandException $e) {
            $caught = $e;
        }
        $this->assertInstanceOf(InvalidCommandException::class, $caught);
        $this->assertArrayHasKey($expectedField, $caught->getErrors());
    }
}
