<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Store\DeleteStore;

use App\Application\Shared\Exception\InvalidCommandException;
use App\Application\Store\Command\DeleteStore\DeleteStoreCommand;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DeleteStoreCommandTest extends TestCase
{
    private static function base(): array
    {
        return [
            'id' => '550e8400-e29b-41d4-a716-446655440000',
            'requested_by' => '550e8400-e29b-41d4-a716-446655440001',
            'is_admin' => false,
        ];
    }

    public function testValidDataReturnsCommand(): void
    {
        $cmd = DeleteStoreCommand::validateAndCreate(self::base());
        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', $cmd->id);
        $this->assertSame('550e8400-e29b-41d4-a716-446655440001', $cmd->requestedBy);
        $this->assertFalse($cmd->isAdmin);
    }

    /** @return array<string, array{array<string, mixed>, string}> */
    public static function invalidIdProvider(): array
    {
        return [
            // id — stringNotEmpty
            'id missing' => [['requested_by' => 'user-id'], 'id'],
            'id empty' => [[...self::base(), 'id' => ''], 'id'],
            'id whitespace' => [[...self::base(), 'id' => '   '], 'id'],

            // requested_by — stringNotEmpty
            'requested_by missing' => [['id' => '550e8400-e29b-41d4-a716-446655440000'], 'requested_by'],
            'requested_by empty' => [[...self::base(), 'requested_by' => ''], 'requested_by'],
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    #[DataProvider('invalidIdProvider')]
    public function testInvalidIdThrowsWithError(array $data, string $expectedField): void
    {
        $caught = null;
        try {
            DeleteStoreCommand::validateAndCreate($data);
        } catch (InvalidCommandException $e) {
            $caught = $e;
        }
        $this->assertInstanceOf(InvalidCommandException::class, $caught);
        $this->assertArrayHasKey($expectedField, $caught->getErrors());
    }
}
