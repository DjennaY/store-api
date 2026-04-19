<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\User\LoginUser;

use App\Application\Shared\Exception\InvalidCommandException;
use App\Application\User\Command\LoginUser\LoginUserCommand;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class LoginUserCommandTest extends TestCase
{
    public function testValidDataReturnsCommand(): void
    {
        $cmd = LoginUserCommand::validateAndCreate(['email' => 'jean@example.com', 'password' => 'secret']);
        $this->assertSame('jean@example.com', $cmd->email);
        $this->assertSame('secret', $cmd->plainPassword);
    }

    public function testBothFieldsMissingContainsBothErrors(): void
    {
        $caught = null;
        try {
            LoginUserCommand::validateAndCreate([]);
        } catch (InvalidCommandException $e) {
            $caught = $e;
        }
        $this->assertInstanceOf(InvalidCommandException::class, $caught);
        $this->assertArrayHasKey('email', $caught->getErrors());
        $this->assertArrayHasKey('password', $caught->getErrors());
    }

    /** @return array<string, array{array<string, mixed>, string}> */
    public static function invalidFieldsProvider(): array
    {
        return [
            // email — Assert::email
            'email empty' => [['email' => '', 'password' => 'secret'], 'email'],
            'email no at sign' => [['email' => 'jeanexample.com', 'password' => 'secret'], 'email'],
            'email no domain' => [['email' => 'jean@', 'password' => 'secret'], 'email'],
            'email no local part' => [['email' => '@example.com', 'password' => 'secret'], 'email'],
            'email plain string' => [['email' => 'notanemail', 'password' => 'secret'], 'email'],

            // password — stringNotEmpty
            'password empty' => [['email' => 'jean@example.com', 'password' => ''], 'password'],
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
            LoginUserCommand::validateAndCreate($data);
        } catch (InvalidCommandException $e) {
            $caught = $e;
        }
        $this->assertInstanceOf(InvalidCommandException::class, $caught);
        $this->assertArrayHasKey($expectedField, $caught->getErrors());
    }
}
