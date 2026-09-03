<?php

namespace App\Services\Platform;

use App\Models\Platform\PlatformSetting;
use Illuminate\Support\Facades\Config;

/**
 * Applies Super Admin platform_settings SMTP config to Laravel Mail at runtime.
 */
class MailConfigService
{
    public static function isConfigured(): bool
    {
        $host = PlatformSetting::get('mail_host');

        return is_string($host) && trim($host) !== '';
    }

    /** @return bool True when platform SMTP was applied. */
    public static function apply(): bool
    {
        if (! static::isConfigured()) {
            return false;
        }

        $port = (int) (PlatformSetting::get('mail_port') ?: 587);

        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp.transport', 'smtp');
        Config::set('mail.mailers.smtp.host', PlatformSetting::get('mail_host'));
        Config::set('mail.mailers.smtp.port', $port);
        Config::set('mail.mailers.smtp.username', PlatformSetting::get('mail_username'));
        Config::set('mail.mailers.smtp.password', PlatformSetting::get('mail_password'));
        Config::set('mail.mailers.smtp.scheme', static::schemeForPort($port));
        Config::set('mail.mailers.smtp.url', null);

        $fromEmail = PlatformSetting::get('mail_from_email');
        $fromName = PlatformSetting::get('mail_from_name') ?: config('app.name');

        if (is_string($fromEmail) && trim($fromEmail) !== '') {
            Config::set('mail.from.address', $fromEmail);
            Config::set('mail.from.name', $fromName);
        }

        return true;
    }

    private static function schemeForPort(int $port): ?string
    {
        return match ($port) {
            465 => 'smtps',
            default => null,
        };
    }
}
