<?php

namespace App\Services\Platform;

use App\Models\Platform\MasterCountry;
use App\Models\Platform\Tenant;

class CurrencyService
{
    /**
     * Common ISO currencies for SuperAdmin country master dropdown.
     *
     * @return array<string, array{code: string, symbol: string, name: string}>
     */
    public static function commonCurrencies(): array
    {
        return [
            'INR' => ['code' => 'INR', 'symbol' => '₹', 'name' => 'Indian Rupee'],
            'USD' => ['code' => 'USD', 'symbol' => '$', 'name' => 'US Dollar'],
            'AED' => ['code' => 'AED', 'symbol' => 'د.إ', 'name' => 'UAE Dirham'],
            'GBP' => ['code' => 'GBP', 'symbol' => '£', 'name' => 'British Pound'],
            'EUR' => ['code' => 'EUR', 'symbol' => '€', 'name' => 'Euro'],
            'SAR' => ['code' => 'SAR', 'symbol' => '﷼', 'name' => 'Saudi Riyal'],
            'QAR' => ['code' => 'QAR', 'symbol' => 'ر.ق', 'name' => 'Qatari Riyal'],
            'KWD' => ['code' => 'KWD', 'symbol' => 'د.ك', 'name' => 'Kuwaiti Dinar'],
            'OMR' => ['code' => 'OMR', 'symbol' => 'ر.ع.', 'name' => 'Omani Rial'],
            'BHD' => ['code' => 'BHD', 'symbol' => '.د.ب', 'name' => 'Bahraini Dinar'],
            'SGD' => ['code' => 'SGD', 'symbol' => 'S$', 'name' => 'Singapore Dollar'],
            'AUD' => ['code' => 'AUD', 'symbol' => 'A$', 'name' => 'Australian Dollar'],
            'CAD' => ['code' => 'CAD', 'symbol' => 'C$', 'name' => 'Canadian Dollar'],
            'NPR' => ['code' => 'NPR', 'symbol' => 'रू', 'name' => 'Nepalese Rupee'],
            'BDT' => ['code' => 'BDT', 'symbol' => '৳', 'name' => 'Bangladeshi Taka'],
            'LKR' => ['code' => 'LKR', 'symbol' => 'Rs', 'name' => 'Sri Lankan Rupee'],
            'PKR' => ['code' => 'PKR', 'symbol' => 'Rs', 'name' => 'Pakistani Rupee'],
            'MYR' => ['code' => 'MYR', 'symbol' => 'RM', 'name' => 'Malaysian Ringgit'],
            'KES' => ['code' => 'KES', 'symbol' => 'KSh', 'name' => 'Kenyan Shilling'],
            'NGN' => ['code' => 'NGN', 'symbol' => '₦', 'name' => 'Nigerian Naira'],
            'ZAR' => ['code' => 'ZAR', 'symbol' => 'R', 'name' => 'South African Rand'],
        ];
    }

    /**
     * Approximate INR value of 1 unit of currency (for SaaS plan display conversion).
     * Override per country via tbl_master_countries.fx_inr_per_unit when needed.
     *
     * @return array<string, float>
     */
    public static function defaultFxInrPerUnit(): array
    {
        return [
            'INR' => 1.0,
            'USD' => 83.0,
            'AUD' => 55.0,
            'AED' => 22.5,
            'GBP' => 105.0,
            'EUR' => 90.0,
            'SAR' => 22.1,
            'QAR' => 22.8,
            'KWD' => 270.0,
            'OMR' => 215.0,
            'BHD' => 220.0,
            'SGD' => 62.0,
            'CAD' => 60.0,
            'NPR' => 0.63,
            'BDT' => 0.76,
            'LKR' => 0.27,
            'PKR' => 0.30,
            'MYR' => 18.5,
            'KES' => 0.64,
            'NGN' => 0.055,
            'ZAR' => 4.5,
        ];
    }

    /**
     * Common ISO 3166-1 alpha-2 country codes for CSC master.
     *
     * @return array<string, string> code => country name hint
     */
    public static function commonCountryCodes(): array
    {
        return [
            'IN' => 'India',
            'AU' => 'Australia',
            'US' => 'United States',
            'GB' => 'United Kingdom',
            'AE' => 'United Arab Emirates',
            'SA' => 'Saudi Arabia',
            'SG' => 'Singapore',
            'CA' => 'Canada',
            'NP' => 'Nepal',
            'BD' => 'Bangladesh',
            'LK' => 'Sri Lanka',
            'PK' => 'Pakistan',
            'QA' => 'Qatar',
            'KW' => 'Kuwait',
            'OM' => 'Oman',
            'BH' => 'Bahrain',
            'MY' => 'Malaysia',
            'KE' => 'Kenya',
            'NG' => 'Nigeria',
            'ZA' => 'South Africa',
            'DE' => 'Germany',
            'FR' => 'France',
            'NZ' => 'New Zealand',
        ];
    }

    public static function fxForCurrencyCode(string $currencyCode): float
    {
        $map = self::defaultFxInrPerUnit();

        return (float) ($map[strtoupper($currencyCode)] ?? 1.0);
    }

    /**
     * @return array{code: string, symbol: string, name: string|null}
     */
    public function getCurrencyForCountry(string $countryName): array
    {
        $normalized = MasterCountry::normalize($countryName);
        $country = MasterCountry::whereRaw('LOWER(name) = ?', [strtolower($normalized)])->first();

        if ($country) {
            return [
                'code' => $country->currency_code ?: 'INR',
                'symbol' => $country->currency_symbol ?: '₹',
                'name' => $country->currency_name,
            ];
        }

        return [
            'code' => config('app.platform_currency_code', 'INR'),
            'symbol' => config('app.platform_currency_symbol', '₹'),
            'name' => null,
        ];
    }

    public function syncFromCountry(Tenant $tenant): void
    {
        if ($tenant->is_currency_override) {
            return;
        }

        if (! $tenant->country) {
            return;
        }

        $currency = $this->getCurrencyForCountry($tenant->country);

        $tenant->update([
            'currency_code' => $currency['code'],
            'currency_symbol' => $currency['symbol'],
        ]);
    }

    public static function currentCode(): string
    {
        return (string) config('app.hospital_currency_code', config('app.platform_currency_code', 'INR'));
    }

    public static function currentSymbol(): string
    {
        return (string) config('app.hospital_currency_symbol', config('app.platform_currency_symbol', '₹'));
    }

    public static function format(float|int|string|null $amount, int $decimals = 2): string
    {
        return self::currentSymbol().number_format((float) ($amount ?? 0), $decimals);
    }

    public static function formatWithCode(float|int|string|null $amount, int $decimals = 2): string
    {
        return self::currentCode().' '.number_format((float) ($amount ?? 0), $decimals);
    }
}
