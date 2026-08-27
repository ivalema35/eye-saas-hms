<?php

namespace App\Services\Platform;

use App\Models\Platform\MasterCountry;
use App\Models\Platform\Tenant;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

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
            'PHP' => ['code' => 'PHP', 'symbol' => '₱', 'name' => 'Philippine Peso'],
            'VND' => ['code' => 'VND', 'symbol' => '₫', 'name' => 'Vietnamese Dong'],
            'THB' => ['code' => 'THB', 'symbol' => '฿', 'name' => 'Thai Baht'],
            'IDR' => ['code' => 'IDR', 'symbol' => 'Rp', 'name' => 'Indonesian Rupiah'],
            'KES' => ['code' => 'KES', 'symbol' => 'KSh', 'name' => 'Kenyan Shilling'],
            'NGN' => ['code' => 'NGN', 'symbol' => '₦', 'name' => 'Nigerian Naira'],
            'ZAR' => ['code' => 'ZAR', 'symbol' => 'R', 'name' => 'South African Rand'],
        ];
    }

    /**
     * Approximate INR value of 1 unit of currency (for SaaS plan display conversion).
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
            'PHP' => 1.45,
            'VND' => 0.0033,
            'THB' => 2.4,
            'IDR' => 0.0052,
            'KES' => 0.64,
            'NGN' => 0.055,
            'ZAR' => 4.5,
        ];
    }

    /**
     * @return array<string, string>
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
            'PH' => 'Philippines',
            'VN' => 'Vietnam',
            'TH' => 'Thailand',
            'ID' => 'Indonesia',
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

    public static function findCountryByName(?string $countryName): ?MasterCountry
    {
        $name = trim((string) $countryName);
        if ($name === '') {
            return null;
        }

        return MasterCountry::query()
            ->whereRaw('LOWER(name) = ?', [strtolower($name)])
            ->first();
    }

    /**
     * @return array{code: string, symbol: string, name: string|null, timezone: string|null}
     */
    public function getCurrencyForCountry(string $countryName): array
    {
        $country = self::findCountryByName($countryName);

        if ($country) {
            return [
                'code' => $country->currency_code ?: 'INR',
                'symbol' => $country->currency_symbol ?: '₹',
                'name' => $country->currency_name,
                'timezone' => $country->default_timezone ?: null,
            ];
        }

        return [
            'code' => config('app.platform_currency_code', 'INR'),
            'symbol' => config('app.platform_currency_symbol', '₹'),
            'name' => null,
            'timezone' => null,
        ];
    }

    /**
     * Apply hospital currency (and timezone) for the active request.
     * Prefer Super Admin country master linked to tenant.country.
     */
    public static function applyTenantCurrencyToConfig(?Tenant $tenant): void
    {
        if (! $tenant) {
            return;
        }

        $platformCode = (string) config('app.platform_currency_code', 'INR');
        $platformSymbol = (string) config('app.platform_currency_symbol', '₹');
        $code = $platformCode;
        $symbol = $platformSymbol;

        // Prefer hospital settings country (Settings page) over legacy tenant.country mismatch
        $settingsCountry = DB::table('hospital_settings')
            ->where('tenant_id', $tenant->id)
            ->where('key', 'hospital_country')
            ->value('value');
        $countryName = trim((string) ($settingsCountry ?: $tenant->country));
        $master = $countryName !== '' ? self::findCountryByName($countryName) : null;

        if ($tenant->is_currency_override) {
            $code = $tenant->currency_code ?: $platformCode;
            $symbol = $tenant->currency_symbol ?: $platformSymbol;
        } elseif ($master) {
            $code = $master->currency_code ?: ($tenant->currency_code ?: $platformCode);
            $symbol = $master->currency_symbol ?: ($tenant->currency_symbol ?: $platformSymbol);

            // Master row incomplete → infer from ISO country code when possible
            if ((! $master->currency_code || ! $master->currency_symbol) && $master->country_code) {
                $iso = strtoupper((string) $master->country_code);
                $isoCurrency = [
                    'IN' => 'INR', 'DE' => 'EUR', 'FR' => 'EUR', 'US' => 'USD',
                    'GB' => 'GBP', 'AE' => 'AED', 'AU' => 'AUD', 'CA' => 'CAD',
                    'SA' => 'SAR', 'SG' => 'SGD', 'NP' => 'NPR', 'BD' => 'BDT',
                    'PH' => 'PHP', 'VN' => 'VND', 'TH' => 'THB', 'ID' => 'IDR',
                    'LK' => 'LKR', 'PK' => 'PKR', 'MY' => 'MYR',
                ];
                if (isset($isoCurrency[$iso])) {
                    $preset = self::commonCurrencies()[$isoCurrency[$iso]] ?? null;
                    if ($preset) {
                        $code = $master->currency_code ?: $preset['code'];
                        $symbol = $master->currency_symbol ?: $preset['symbol'];
                    }
                }
            }
        } else {
            $code = $tenant->currency_code ?: $platformCode;
            $symbol = $tenant->currency_symbol ?: $platformSymbol;
        }

        Config::set('app.hospital_currency_code', $code);
        Config::set('app.hospital_currency_symbol', $symbol);

        $timezone = $tenant->timezone ?: 'UTC';
        // Prefer settings timezone if set
        $settingsTimezone = DB::table('hospital_settings')
            ->where('tenant_id', $tenant->id)
            ->where('key', 'hospital_timezone')
            ->value('value');
        if ($settingsTimezone && in_array($settingsTimezone, timezone_identifiers_list(), true)) {
            $timezone = $settingsTimezone;
        }
        if (! $tenant->is_timezone_override && $master?->default_timezone && ! $settingsTimezone) {
            $timezone = $master->default_timezone;
        }
        if (in_array($timezone, timezone_identifiers_list(), true)) {
            Config::set('app.hospital_timezone', $timezone);
            date_default_timezone_set($timezone);
        }
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

        $payload = [
            'currency_code' => $currency['code'],
            'currency_symbol' => $currency['symbol'],
        ];

        if (! $tenant->is_timezone_override && ! empty($currency['timezone'])) {
            $payload['timezone'] = $currency['timezone'];
        }

        $tenant->update($payload);
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
