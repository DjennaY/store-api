<?php

declare(strict_types=1);

namespace App\Domain\Store\ValueObject;

use App\Shared\Exception\ValidationException;

final readonly class StoreAddress
{
    private string $countryIso;

    public function __construct(
        private string $street,
        private string $city,
        private string $zipCode,
        string $countryIso,
    ) {
        if (!preg_match('/^\d{5}$/', $zipCode)) {
            throw new ValidationException('zip_code must be exactly 5 digits.');
        }
        $upper = strtoupper(trim($countryIso));
        if (!preg_match('/^[A-Z]{2}$/', $upper)) {
            throw new ValidationException('country_iso must be a 2-letter ISO code (e.g. FR).');
        }
        $this->countryIso = $upper;
    }

    public function getStreet(): string
    {
        return $this->street;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getZipCode(): string
    {
        return $this->zipCode;
    }

    public function getCountryIso(): string
    {
        return $this->countryIso;
    }
}
