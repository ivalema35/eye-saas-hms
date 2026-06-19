<?php

namespace App\Services\Platform;

use App\Models\Platform\MasterCountry;
use App\Models\Platform\Tenant;
use Carbon\Carbon;

class TimezoneService
{
    /**
     * Country name se default timezone lookup karo.
     * Agar country nahi mili toh 'UTC' return karo.
     */
    public function getTimezoneForCountry(string $countryName): string
    {
        $normalized = MasterCountry::normalize($countryName);

        $country = MasterCountry::whereRaw('LOWER(name) = ?', [strtolower($normalized)])->first();

        return $country?->default_timezone ?? 'UTC';
    }

    /**
     * Hospital (Tenant) ki country ka default timezone assign karo.
     * Sirf tab karo jab is_timezone_override = false ho.
     */
    public function syncFromCountry(Tenant $tenant): void
    {
        if ($tenant->is_timezone_override) {
            return;
        }

        if (! $tenant->country) {
            return;
        }

        $timezone = $this->getTimezoneForCountry($tenant->country);

        $tenant->update(['timezone' => $timezone]);
    }

    /**
     * Hospital Admin ne manually timezone set kiya — override flag set karo.
     */
    public function setOverride(Tenant $tenant, string $timezone): void
    {
        abort_unless(in_array($timezone, timezone_identifiers_list()), 422, 'Invalid timezone.');

        $tenant->update([
            'timezone' => $timezone,
            'is_timezone_override' => true,
        ]);
    }

    /**
     * Override hatao — country ke default par wapas jao.
     */
    public function resetToCountryDefault(Tenant $tenant): void
    {
        $tenant->update(['is_timezone_override' => false]);
        $this->syncFromCountry($tenant);
    }

    /**
     * UTC Carbon datetime ko hospital timezone me convert karo (display ke liye).
     */
    public function toLocal(Carbon $utcDateTime, string $timezone): Carbon
    {
        return $utcDateTime->copy()->setTimezone($timezone);
    }

    /**
     * Local datetime ko UTC me convert karo (database save ke liye).
     */
    public function toUtc(Carbon $localDateTime, string $timezone): Carbon
    {
        return $localDateTime->copy()->setTimezone('UTC');
    }

    /**
     * Current hospital timezone get karo (middleware se set hoti hai).
     */
    public static function current(): string
    {
        return config('app.hospital_timezone', config('app.timezone', 'UTC'));
    }

    /**
     * UTC Carbon ko current hospital timezone me format karke string return karo.
     */
    public static function format(Carbon $utcDateTime, string $format = 'd M Y, h:i A'): string
    {
        $tz = self::current();
        return $utcDateTime->copy()->setTimezone($tz)->format($format);
    }

    /**
     * PHP ke saare valid timezone identifiers grouped by region.
     * Blade dropdown ke liye use karo.
     */
    public static function groupedTimezones(): array
    {
        $zones = [];
        foreach (timezone_identifiers_list() as $tz) {
            $parts = explode('/', $tz, 2);
            $region = $parts[0];
            $zones[$region][] = $tz;
        }
        ksort($zones);
        return $zones;
    }
}
