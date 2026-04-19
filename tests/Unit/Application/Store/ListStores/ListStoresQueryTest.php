<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Store\ListStores;

use App\Application\Shared\Exception\InvalidCommandException;
use App\Application\Store\Query\ListStores\ListStoresQuery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ListStoresQueryTest extends TestCase
{
    public function testDefaultsAreAppliedWhenNoParamsGiven(): void
    {
        $query = ListStoresQuery::validateAndCreate([]);
        $this->assertSame('created_at', $query->sortBy);
        $this->assertSame('DESC', $query->sortOrder);
        $this->assertSame(20, $query->limit);
        $this->assertSame(0, $query->offset);
        $this->assertNull($query->name);
        $this->assertNull($query->city);
        $this->assertNull($query->countryIso);
    }

    public function testValidParamsAreApplied(): void
    {
        $query = ListStoresQuery::validateAndCreate([
            'name' => 'Apple',
            'city' => 'Paris',
            'country_iso' => 'fr',
            'sort_by' => 'name',
            'sort_order' => 'asc',
            'limit' => '50',
            'offset' => '10',
        ]);

        $this->assertSame('Apple', $query->name);
        $this->assertSame('Paris', $query->city);
        $this->assertSame('FR', $query->countryIso);
        $this->assertSame('name', $query->sortBy);
        $this->assertSame('ASC', $query->sortOrder);
        $this->assertSame(50, $query->limit);
        $this->assertSame(10, $query->offset);
    }

    public function testEmptyStringFiltersAreNormalisedToNull(): void
    {
        $query = ListStoresQuery::validateAndCreate(['name' => '', 'city' => '', 'country_iso' => '']);
        $this->assertNull($query->name);
        $this->assertNull($query->city);
        $this->assertNull($query->countryIso);
    }

    public function testNameIsTrimmed(): void
    {
        $query = ListStoresQuery::validateAndCreate(['name' => '  Apple  ']);
        $this->assertSame('Apple', $query->name);
    }

    public function testCityIsTrimmed(): void
    {
        $query = ListStoresQuery::validateAndCreate(['city' => '  Paris  ']);
        $this->assertSame('Paris', $query->city);
    }

    public function testCountryIsoIsNormalisedToUppercase(): void
    {
        $query = ListStoresQuery::validateAndCreate(['country_iso' => 'fr']);
        $this->assertSame('FR', $query->countryIso);
    }

    public function testCountryIsoIsTrimmedAndUppercased(): void
    {
        $query = ListStoresQuery::validateAndCreate(['country_iso' => '  fr  ']);
        $this->assertSame('FR', $query->countryIso);
    }

    /** @return array<string, array{array<string, mixed>, string}> */
    public static function invalidParamsProvider(): array
    {
        return [
            // name — maxLength(100)
            'name exceeds 100 chars' => [['name' => str_repeat('a', 101)], 'name'],

            // city — maxLength(100)
            'city exceeds 100 chars' => [['city' => str_repeat('a', 101)], 'city'],

            // country_iso — regex /^[A-Za-z]{2}$/
            'country_iso 1 letter' => [['country_iso' => 'F'],   'country_iso'],
            'country_iso 3 letters' => [['country_iso' => 'FRA'], 'country_iso'],
            'country_iso digits' => [['country_iso' => '33'],  'country_iso'],

            // sort_by — must be one of allowed values
            'sort_by invalid' => [['sort_by' => 'unknown'], 'sort_by'],

            // sort_order — must be ASC or DESC
            'sort_order invalid' => [['sort_order' => 'RANDOM'], 'sort_order'],

            // limit — must be between 1 and 100
            'limit zero' => [['limit' => '0'],   'limit'],
            'limit negative' => [['limit' => '-1'],  'limit'],
            'limit above 100' => [['limit' => '101'], 'limit'],

            // offset — must be >= 0
            'offset negative' => [['offset' => '-1'], 'offset'],
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    #[DataProvider('invalidParamsProvider')]
    public function testInvalidParamThrowsWithError(array $data, string $expectedField): void
    {
        $caught = null;
        try {
            ListStoresQuery::validateAndCreate($data);
        } catch (InvalidCommandException $e) {
            $caught = $e;
        }
        $this->assertInstanceOf(InvalidCommandException::class, $caught);
        $this->assertArrayHasKey($expectedField, $caught->getErrors());
    }
}
