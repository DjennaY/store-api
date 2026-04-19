<?php

declare(strict_types=1);

namespace App\Domain\Store\ValueObject;

/**
 * Deduplication natural key for a store.
 *
 * Composed of: name | address | city | zip_code | country_iso
 * Each segment is normalised: strtolower(trim($value))
 *
 * Comparison is case-insensitive and trim-insensitive.
 */
final readonly class NaturalKey
{
    private string $value;

    public function __construct(
        string $name,
        string $address,
        string $city,
        string $zipCode,
        string $countryIso,
    ) {
        $normalize = static fn (string $s): string => strtolower(trim($s));
        $this->value = implode('|', array_map($normalize, [$name, $address, $city, $zipCode, $countryIso]));
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
