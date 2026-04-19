<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\User\CreateAdmin;

use App\Application\Shared\Exception\InvalidCommandException;
use App\Application\User\Command\CreateAdmin\CreateAdminCommand;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CreateAdminCommandTest extends TestCase
{
    /** @return array<string, mixed> */
    private static function base(): array
    {
        return [
            'email' => 'admin@example.com',
            'password' => 'securepass123',
            'first_name' => 'Admin',
            'last_name' => 'User',
        ];
    }

    public function testValidDataReturnsCommand(): void
    {
        $cmd = CreateAdminCommand::validateAndCreate(self::base());
        $this->assertSame('admin@example.com', $cmd->email);
        $this->assertSame('securepass123', $cmd->plainPassword);
        $this->assertSame('Admin', $cmd->firstName);
        $this->assertSame('User', $cmd->lastName);
    }

    public function testEmailIsTrimmed(): void
    {
        $cmd = CreateAdminCommand::validateAndCreate([...self::base(), 'email' => '  admin@example.com  ']);
        $this->assertSame('admin@example.com', $cmd->email);
    }

    public function testFirstNameIsTrimmed(): void
    {
        $cmd = CreateAdminCommand::validateAndCreate([...self::base(), 'first_name' => '  Admin  ']);
        $this->assertSame('Admin', $cmd->firstName);
    }

    public function testLastNameIsTrimmed(): void
    {
        $cmd = CreateAdminCommand::validateAndCreate([...self::base(), 'last_name' => '  User  ']);
        $this->assertSame('User', $cmd->lastName);
    }

    public function testAllRequiredFieldsMissingContainsAllErrors(): void
    {
        $caught = null;
        try {
            CreateAdminCommand::validateAndCreate([]);
        } catch (InvalidCommandException $e) {
            $caught = $e;
        }
        $this->assertInstanceOf(InvalidCommandException::class, $caught);
        foreach (['email', 'password', 'first_name', 'last_name'] as $field) {
            $this->assertArrayHasKey($field, $caught->getErrors(), "Missing error for: {$field}");
        }
    }

    /** @return array<string, array{array<string, mixed>, string}> */
    public static function invalidFieldsProvider(): array
    {
        return [
            // email — valid RFC format required
            'email empty' => [[...self::base(), 'email' => ''], 'email'],
            'email no at sign' => [[...self::base(), 'email' => 'adminexample.com'], 'email'],
            'email no domain' => [[...self::base(), 'email' => 'admin@'], 'email'],
            'email no local part' => [[...self::base(), 'email' => '@example.com'], 'email'],
            'email plain string' => [[...self::base(), 'email' => 'notanemail'], 'email'],

            // password — minLength(8)
            'password empty' => [[...self::base(), 'password' => ''], 'password'],
            'password 7 chars boundary' => [[...self::base(), 'password' => 'pass123'], 'password'],

            // first_name — stringNotEmpty
            'first_name empty' => [[...self::base(), 'first_name' => ''], 'first_name'],

            // last_name — stringNotEmpty
            'last_name empty' => [[...self::base(), 'last_name' => ''], 'last_name'],
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
            CreateAdminCommand::validateAndCreate($data);
        } catch (InvalidCommandException $e) {
            $caught = $e;
        }
        $this->assertInstanceOf(InvalidCommandException::class, $caught);
        $this->assertArrayHasKey($expectedField, $caught->getErrors());
    }
}
