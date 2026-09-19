<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

class CountryResolver
{
    public const BANGLADESH = 'BD';

    public const PROVIDER_SSLCOMMERZ = 'sslcommerz';

    public const PROVIDER_STRIPE = 'stripe';

    /**
     * ISO 3166-1 alpha-3 to alpha-2 for names the storefront actually uses.
     *
     * @var array<string, string>
     */
    private const ALPHA3 = [
        'BGD' => 'BD',
        'DNK' => 'DK',
        'DEU' => 'DE',
        'FRA' => 'FR',
        'GBR' => 'GB',
        'USA' => 'US',
        'SWE' => 'SE',
        'NOR' => 'NO',
        'NLD' => 'NL',
        'ESP' => 'ES',
        'ITA' => 'IT',
        'CAN' => 'CA',
        'AUS' => 'AU',
        'IND' => 'IN',
        'ARE' => 'AE',
        'SGP' => 'SG',
        'JPN' => 'JP',
    ];

    /**
     * Official / storefront English names to ISO 3166-1 alpha-2.
     *
     * @var array<string, string>
     */
    private const NAMES = [
        'BANGLADESH' => 'BD',
        'DENMARK' => 'DK',
        'GERMANY' => 'DE',
        'FRANCE' => 'FR',
        'UNITED KINGDOM' => 'GB',
        'UNITED STATES' => 'US',
        'UNITED STATES OF AMERICA' => 'US',
        'SWEDEN' => 'SE',
        'NORWAY' => 'NO',
        'NETHERLANDS' => 'NL',
        'SPAIN' => 'ES',
        'ITALY' => 'IT',
        'CANADA' => 'CA',
        'AUSTRALIA' => 'AU',
        'INDIA' => 'IN',
        'UNITED ARAB EMIRATES' => 'AE',
        'SINGAPORE' => 'SG',
        'JAPAN' => 'JP',
    ];

    public static function normalize(string $value): string
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            throw ValidationException::withMessages(['shipping_address.country' => 'A valid shipping country is required.']);
        }

        $compact = strtoupper(preg_replace('/\s+/', ' ', $trimmed) ?? $trimmed);

        if (preg_match('/^[A-Z]{2}$/', $compact) === 1) {
            return $compact;
        }

        if (preg_match('/^[A-Z]{3}$/', $compact) === 1 && isset(self::ALPHA3[$compact])) {
            return self::ALPHA3[$compact];
        }

        if (isset(self::NAMES[$compact])) {
            return self::NAMES[$compact];
        }

        throw ValidationException::withMessages(['shipping_address.country' => 'The selected country is not supported.']);
    }

    public static function providerForCountryCode(string $countryCode): string
    {
        return strtoupper($countryCode) === self::BANGLADESH
            ? self::PROVIDER_SSLCOMMERZ
            : self::PROVIDER_STRIPE;
    }

    public static function providerForCountry(string $country): string
    {
        return self::providerForCountryCode(self::normalize($country));
    }
}
