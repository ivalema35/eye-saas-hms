<?php

use App\Models\Hospital\HospitalSetting;
use App\Services\Platform\CurrencyService;

if (! function_exists('currency_code')) {
    /** Active hospital currency ISO code (falls back to platform). */
    function currency_code(): string
    {
        return CurrencyService::currentCode();
    }
}

if (! function_exists('currency_symbol')) {
    /** Active hospital currency symbol (falls back to platform). */
    function currency_symbol(): string
    {
        return CurrencyService::currentSymbol();
    }
}

if (! function_exists('money')) {
    /** Format amount with hospital currency symbol, e.g. ₹1,250.00 */
    function money(float|int|string|null $amount, int $decimals = 2): string
    {
        return CurrencyService::format($amount, $decimals);
    }
}

if (! function_exists('money_code')) {
    /** Format amount with currency code prefix, e.g. INR 1,250.00 */
    function money_code(float|int|string|null $amount, int $decimals = 2): string
    {
        return CurrencyService::formatWithCode($amount, $decimals);
    }
}

if (! function_exists('platform_currency_code')) {
    function platform_currency_code(): string
    {
        return (string) config('app.platform_currency_code', 'INR');
    }
}

if (! function_exists('platform_currency_symbol')) {
    function platform_currency_symbol(): string
    {
        return (string) config('app.platform_currency_symbol', '₹');
    }
}

if (! function_exists('platform_money')) {
    function platform_money(float|int|string|null $amount, int $decimals = 0): string
    {
        return platform_currency_symbol().number_format((float) ($amount ?? 0), $decimals);
    }
}

if (! function_exists('tenant_money')) {
    function tenant_money(\App\Models\Platform\Tenant $tenant, float|int|string|null $amount, int $decimals = 0): string
    {
        $symbol = $tenant->currency_symbol ?: platform_currency_symbol();

        return $symbol.number_format((float) ($amount ?? 0), $decimals);
    }
}

if (! function_exists('platform_settings')) {
    /** Cached platform_settings key-value map (request-scoped). */
    function platform_settings(): array
    {
        static $settings = null;

        if ($settings === null) {
            $settings = \App\Models\Platform\PlatformSetting::query()
                ->pluck('value', 'key')
                ->toArray();
        }

        return $settings;
    }
}

if (! function_exists('platform_setting')) {
    function platform_setting(string $key, mixed $default = null): mixed
    {
        return platform_settings()[$key] ?? $default;
    }
}

if (! function_exists('platform_trial_days')) {
    function platform_trial_days(): int
    {
        return max(1, (int) platform_setting('trial_days', 14));
    }
}

if (! function_exists('platform_gst_rate_india')) {
    function platform_gst_rate_india(): float
    {
        return max(0.0, (float) platform_setting('gst_rate_india', 18));
    }
}

if (! function_exists('platform_country_applies_gst')) {
    /** GST applies to India registrations only. */
    function platform_country_applies_gst(?string $countryCode, ?string $countryName = null): bool
    {
        $code = strtoupper(trim((string) $countryCode));
        if ($code === 'IN') {
            return true;
        }

        return strtolower(trim((string) $countryName)) === 'india';
    }
}

if (! function_exists('platform_trial_label')) {
    /** e.g. "14-day" for UI copy */
    function platform_trial_label(): string
    {
        return platform_trial_days().'-day';
    }
}

if (! function_exists('hospital_settings')) {
    function hospital_settings(): array
    {
        static $settings = null;

        if ($settings === null) {
            $settings = HospitalSetting::query()
                ->pluck('value', 'key')
                ->toArray();
        }

        return $settings;
    }
}

if (! function_exists('hospital_setting')) {
    function hospital_setting(string $key, mixed $default = null): mixed
    {
        return hospital_settings()[$key] ?? $default;
    }
}

if (! function_exists('hospital_name')) {
    function hospital_name(?string $default = null): string
    {
        return (string) (hospital_setting('hospital_name', $default ?? (app('tenant')?->name ?? config('app.name'))));
    }
}

if (! function_exists('hospital_full_address')) {
    function hospital_full_address(?string $default = null): string
    {
        return (string) hospital_setting('hospital_address', $default ?? '');
    }
}

if (! function_exists('hospital_official_email')) {
    function hospital_official_email(?string $default = null): string
    {
        return (string) hospital_setting('hospital_email', $default ?? (app('tenant')?->admin_email ?? ''));
    }
}

if (! function_exists('hospital_contact_number')) {
    function hospital_contact_number(?string $default = null): string
    {
        return (string) hospital_setting('hospital_phone', $default ?? (app('tenant')?->admin_phone ?? ''));
    }
}

if (! function_exists('public_asset_url')) {
    /**
     * URL for a file under /public that works in subdirectory installs
     * (e.g. http://localhost/eye-saas/public/...) even when APP_URL is only the host.
     */
    function public_asset_url(string $relativePath): string
    {
        $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
        $absolutePath = public_path($relativePath);
        $version = is_file($absolutePath) ? (string) filemtime($absolutePath) : null;

        if (! app()->runningInConsole() && isset($_SERVER['SCRIPT_NAME'])) {
            $script = str_replace('\\', '/', (string) $_SERVER['SCRIPT_NAME']);
            $base = str_ends_with($script, '/index.php')
                ? substr($script, 0, -strlen('/index.php'))
                : rtrim(dirname($script), '/');
            $url = ($base === '' || $base === '/' ? '' : $base).'/'.$relativePath;
        } else {
            $url = asset($relativePath);
        }

        return $version !== null ? $url.'?v='.$version : $url;
    }
}

if (! function_exists('platform_logo_url')) {
    function platform_logo_url(): string
    {
        return public_asset_url('images/eye-hms-logo.png');
    }
}

if (! function_exists('platform_favicon_url')) {
    function platform_favicon_url(): string
    {
        return public_asset_url('images/favicon.png');
    }
}

if (! function_exists('platform_logo_light_url')) {
    /** Blue logo for white/light backgrounds (sidebar, login forms). */
    function platform_logo_light_url(): string
    {
        return public_asset_url('images/eye-hms-logo1.png');
    }
}

if (! function_exists('public_storage_url')) {
    /**
     * URL for a file on the public disk — uses /files/ route (no symlink required).
     */
    function public_storage_url(?string $relativePath): ?string
    {
        return \App\Support\PublicStorage::url($relativePath);
    }
}

if (! function_exists('hospital_logo_path')) {
    function hospital_logo_path(): ?string
    {
        $path = hospital_setting('hospital_logo');

        return is_string($path) && $path !== '' ? $path : null;
    }
}

if (! function_exists('hospital_logo_url')) {
    function hospital_logo_url(): string
    {
        $path = hospital_logo_path();

        return public_storage_url($path) ?? platform_logo_url();
    }
}

if (! function_exists('hospital_logo_light_url')) {
    /** Hospital logo for white/light backgrounds (login, settings preview). */
    function hospital_logo_light_url(): string
    {
        $path = hospital_logo_path();

        return public_storage_url($path) ?? platform_logo_light_url();
    }
}

if (! function_exists('hospital_sidebar_logo_url')) {
    function hospital_sidebar_logo_url(): string
    {
        $nobg = hospital_setting('hospital_logo_nobg');
        $style = hospital_setting('logo_sidebar_style') ?? 'white';

        if ($style === 'white' && is_string($nobg) && $nobg !== '') {
            if ($url = public_storage_url($nobg)) {
                return $url;
            }
        }

        $path = hospital_logo_path();

        return public_storage_url($path) ?? platform_logo_url();
    }
}

if (! function_exists('hospital_brand')) {
    function hospital_brand(): array
    {
        return [
            'name' => hospital_name(),
            'full_address' => hospital_full_address(),
            'official_email' => hospital_official_email(),
            'contact_number' => hospital_contact_number(),
            'logo_path' => hospital_logo_path(),
            'logo_url' => hospital_logo_url(),
            'settings' => hospital_settings(),
        ];
    }
}

if (! function_exists('axis_chip')) {
    /**
     * Axis value for PG/ST display/print (plain text with °, no chip).
     */
    function axis_chip(mixed $value, string $empty = ''): string
    {
        $raw = trim((string) ($value ?? ''));
        if ($raw === '' || $raw === '-' || $raw === '—') {
            return $empty;
        }

        $num = preg_replace('/\s*°\s*$/u', '', $raw) ?? $raw;
        $num = trim((string) $num);
        if ($num === '') {
            return $empty;
        }

        return e($num).'°';
    }
}

if (! function_exists('pg_rx_line')) {
    /**
     * PG / NrPG line: "SPH / CYL X <axis-chip>".
     * Returns HTML (use {!! !!}); empty string if all blank.
     */
    function pg_rx_line(mixed $sph, mixed $cyl, mixed $axis): string
    {
        $s = trim((string) ($sph ?? ''));
        $c = trim((string) ($cyl ?? ''));
        $a = trim((string) ($axis ?? ''));

        if ($s === '' && $c === '' && $a === '') {
            return '';
        }

        $out = e($s !== '' ? $s : '-').' / '.e($c !== '' ? $c : '-');
        if ($a !== '') {
            $out .= ' X '.axis_chip($a);
        }

        return $out;
    }
}

if (! function_exists('axis_chip_css')) {
    /** Shared CSS for axis display — no-op (plain text only). */
    function axis_chip_css(): string
    {
        return '';
    }
}

if (! function_exists('hospital_can')) {
    /** Current hospital user has permission (matrix or legacy key). */
    function hospital_can(string $permissionKey): bool
    {
        return app(\App\Services\Auth\RolePermissionService::class)->can($permissionKey);
    }
}

if (! function_exists('hospital_can_any')) {
    /**
     * @param  array<int, string>|string  $permissionKeys  Array or pipe-separated keys
     */
    function hospital_can_any(array|string $permissionKeys): bool
    {
        if (is_string($permissionKeys)) {
            $permissionKeys = array_values(array_filter(array_map('trim', explode('|', $permissionKeys))));
        }

        return app(\App\Services\Auth\RolePermissionService::class)->canAny($permissionKeys);
    }
}
