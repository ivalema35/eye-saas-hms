<?php

use App\Models\Hospital\HospitalSetting;

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
        return (string) hospital_setting('hospital_email', $default ?? '');
    }
}

if (! function_exists('hospital_contact_number')) {
    function hospital_contact_number(?string $default = null): string
    {
        return (string) hospital_setting('hospital_phone', $default ?? '');
    }
}

if (! function_exists('platform_logo_url')) {
    function platform_logo_url(): string
    {
        return asset('images/eye-hms-logo.png');
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

        return $path
            ? asset('storage/'.$path)
            : platform_logo_url();
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
