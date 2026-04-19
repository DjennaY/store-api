<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Store\GetStore;

use App\Application\Shared\Exception\InvalidCommandException;
use App\Application\Store\Query\GetStore\GetStoreQuery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class GetStoreQueryTest extends TestCase
{
    public function testValidIdReturnsQuery(): void
    {
        $query = GetStoreQuery::validateAndCreate(['id' => '550e8400-e29b-41d4-a716-446655440000']);
        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', $query->id);
    }

    /** @return array<string, array{array<string, mixed>, string}> */
    public static function invalidIdProvider(): array
    {
        return [
            // id — stringNotEmpty
            'id missing' => [[], 'id'],
            'id empty' => [['id' => ''], 'id'],
            'id whitespace' => [['id' => '   '], 'id'],
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
            GetStoreQuery::validateAndCreate($data);
        } catch (InvalidCommandException $e) {
            $caught = $e;
        }
        $this->assertInstanceOf(InvalidCommandException::class, $caught);
        $this->assertArrayHasKey($expectedField, $caught->getErrors());
    }
}
