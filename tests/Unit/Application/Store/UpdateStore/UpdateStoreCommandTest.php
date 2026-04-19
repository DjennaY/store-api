<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Store\UpdateStore;

use App\Application\Shared\Exception\InvalidCommandException;
use App\Application\Store\Command\UpdateStore\UpdateStoreCommand;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class UpdateStoreCommandTest extends TestCase
{
    /** @return array<string, mixed> */
    private static function base(): array
    {
        return [
            'id' => '550e8400-e29b-41d4-a716-446655440000',
            'name' => 'Store Updated',
            'address' => '1 rue Test',
            'city' => 'Paris',
            'zip_code' => '75001',
            'country_iso' => 'FR',
            'phone' => '+33123456789',
            'requested_by' => '550e8400-e29b-41d4-a716-446655440001',
            'is_admin' => false,
        ];
    }

    public function testValidDataReturnsCommand(): void
    {
        $cmd = UpdateStoreCommand::validateAndCreate(self::base());
        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', $cmd->id);
        $this->assertSame('Store Updated', $cmd->name);
        $this->assertSame('1 rue Test', $cmd->address);
        $this->assertSame('Paris', $cmd->city);
        $this->assertSame('75001', $cmd->zipCode);
        $this->assertSame('FR', $cmd->countryIso);
        $this->assertSame('+33123456789', $cmd->phone);
        $this->assertSame('550e8400-e29b-41d4-a716-446655440001', $cmd->requestedBy);
        $this->assertFalse($cmd->isAdmin);
    }

    public function testNameIsTrimmed(): void
    {
        $cmd = UpdateStoreCommand::validateAndCreate([...self::base(), 'name' => '  Store Updated  ']);
        $this->assertSame('Store Updated', $cmd->name);
    }

    public function testAddressIsTrimmed(): void
    {
        $cmd = UpdateStoreCommand::validateAndCreate([...self::base(), 'address' => '  1 rue Test  ']);
        $this->assertSame('1 rue Test', $cmd->address);
    }

    public function testCityIsTrimmed(): void
    {
        $cmd = UpdateStoreCommand::validateAndCreate([...self::base(), 'city' => '  Paris  ']);
        $this->assertSame('Paris', $cmd->city);
    }

    public function testZipCodeIsTrimmed(): void
    {
        $cmd = UpdateStoreCommand::validateAndCreate([...self::base(), 'zip_code' => '  75001  ']);
        $this->assertSame('75001', $cmd->zipCode);
    }

    public function testCountryIsoIsNormalizedToUppercase(): void
    {
        $cmd = UpdateStoreCommand::validateAndCreate([...self::base(), 'country_iso' => 'fr']);
        $this->assertSame('FR', $cmd->countryIso);
    }

    public function testCountryIsoIsTrimmed(): void
    {
        $cmd = UpdateStoreCommand::validateAndCreate([...self::base(), 'country_iso' => '  fr  ']);
        $this->assertSame('FR', $cmd->countryIso);
    }

    public function testPhoneIsTrimmed(): void
    {
        $cmd = UpdateStoreCommand::validateAndCreate([...self::base(), 'phone' => '  +33123456789  ']);
        $this->assertSame('+33123456789', $cmd->phone);
    }

    public function testAllRequiredFieldsMissingContainsAllErrors(): void
    {
        $caught = null;
        try {
            UpdateStoreCommand::validateAndCreate([]);
        } catch (InvalidCommandException $e) {
            $caught = $e;
        }
        $this->assertInstanceOf(InvalidCommandException::class, $caught);
        foreach (['id', 'requested_by', 'name', 'address', 'city', 'zip_code', 'country_iso', 'phone'] as $field) {
            $this->assertArrayHasKey($field, $caught->getErrors(), "Missing error for: {$field}");
        }
    }

    /** @return array<string, array{array<string, mixed>, string}> */
    public static function invalidFieldsProvider(): array
    {
        return [
            // id — stringNotEmpty
            'id empty' => [[...self::base(), 'id' => ''], 'id'],

            // requested_by — stringNotEmpty
            'requested_by empty' => [[...self::base(), 'requested_by' => ''], 'requested_by'],

            // name — stringNotEmpty + maxLength(100)
            'name empty' => [[...self::base(), 'name' => ''], 'name'],
            'name exceeds 100 chars' => [[...self::base(), 'name' => str_repeat('a', 101)], 'name'],

            // address — stringNotEmpty
            'address empty' => [[...self::base(), 'address' => ''], 'address'],

            // city — stringNotEmpty
            'city empty' => [[...self::base(), 'city' => ''], 'city'],

            // zip_code — regex /^\d{5}$/
            'zip_code empty' => [[...self::base(), 'zip_code' => ''], 'zip_code'],
            'zip_code 4 digits' => [[...self::base(), 'zip_code' => '7500'], 'zip_code'],
            'zip_code 6 digits' => [[...self::base(), 'zip_code' => '750010'], 'zip_code'],
            'zip_code letters' => [[...self::base(), 'zip_code' => 'ABCDE'], 'zip_code'],
            'zip_code alphanumeric' => [[...self::base(), 'zip_code' => '7500A'], 'zip_code'],

            // country_iso — regex /^[A-Za-z]{2}$/
            'country_iso empty' => [[...self::base(), 'country_iso' => ''], 'country_iso'],
            'country_iso 1 letter' => [[...self::base(), 'country_iso' => 'F'], 'country_iso'],
            'country_iso 3 letters' => [[...self::base(), 'country_iso' => 'FRA'], 'country_iso'],
            'country_iso digits' => [[...self::base(), 'country_iso' => '33'], 'country_iso'],

            // phone — regex /^\+?[\d\s\-]{7,15}$/
            'phone empty' => [[...self::base(), 'phone' => ''], 'phone'],
            'phone too short' => [[...self::base(), 'phone' => '123'], 'phone'],
            'phone too long' => [[...self::base(), 'phone' => '1234567890123456'], 'phone'],
            'phone invalid chars' => [[...self::base(), 'phone' => 'abc+def'], 'phone'],
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
            UpdateStoreCommand::validateAndCreate($data);
        } catch (InvalidCommandException $e) {
            $caught = $e;
        }
        $this->assertInstanceOf(InvalidCommandException::class, $caught);
        $this->assertArrayHasKey($expectedField, $caught->getErrors());
    }
}
