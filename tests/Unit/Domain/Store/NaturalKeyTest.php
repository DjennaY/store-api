<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Store;

use App\Domain\Store\ValueObject\NaturalKey;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class NaturalKeyTest extends TestCase
{
    public function testSameDataProducesSameKey(): void
    {
        $a = new NaturalKey('Apple Store', '1 rue de la Paix', 'Paris', '75001', 'FR');
        $b = new NaturalKey('Apple Store', '1 rue de la Paix', 'Paris', '75001', 'FR');
        $this->assertSame($a->getValue(), $b->getValue());
    }

    public function testDifferentAddressProducesDifferentKey(): void
    {
        $a = new NaturalKey('Apple Store', '1 rue', 'Paris', '75001', 'FR');
        $b = new NaturalKey('Apple Store', '2 rue', 'Paris', '75001', 'FR');
        $this->assertNotSame($a->getValue(), $b->getValue());
    }

    /** @return array<string, array{NaturalKey, NaturalKey}> */
    public static function equivalentKeyProvider(): array
    {
        return [
            'case insensitive' => [
                new NaturalKey('Apple Store', '1 rue de la Paix', 'Paris', '75001', 'FR'),
                new NaturalKey('APPLE STORE', '1 RUE DE LA PAIX', 'PARIS', '75001', 'fr'),
            ],
            'trim insensitive' => [
                new NaturalKey('Apple Store', '1 rue', 'Paris', '75001', 'FR'),
                new NaturalKey('  Apple Store  ', '  1 rue  ', '  Paris  ', '75001', 'FR'),
            ],
        ];
    }

    #[DataProvider('equivalentKeyProvider')]
    public function testEquivalentInputsProduceSameKey(NaturalKey $a, NaturalKey $b): void
    {
        $this->assertSame($a->getValue(), $b->getValue());
    }
}
