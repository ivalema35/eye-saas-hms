<?php

namespace App\Support;

use Closure;

/**
 * Shared email validation rules and messages for the whole app.
 *
 * Uses PHP FILTER_VALIDATE_EMAIL (Laravel "email:filter") so formats like
 * abc@gmail, abc@@gmail.com, and emails with spaces are rejected while
 * normal addresses (user@gmail.com, user@company.in) remain valid.
 *
 * Also rejects near-miss typos of popular providers (e.g. gmailll.com).
 *
 * Do NOT use these rules on login fields that also accept phone numbers.
 */
final class EmailRules
{
    public const RULE = 'email:filter';

    public const MESSAGE = 'Please enter a valid email address.';

    /**
     * Well-known consumer / free-mail domains. Exact matches are always allowed.
     *
     * @var list<string>
     */
    private const COMMON_PROVIDERS = [
        'gmail.com',
        'googlemail.com',
        'yahoo.com',
        'yahoo.co.in',
        'ymail.com',
        'rocketmail.com',
        'hotmail.com',
        'outlook.com',
        'live.com',
        'msn.com',
        'icloud.com',
        'me.com',
        'mac.com',
        'protonmail.com',
        'proton.me',
        'rediffmail.com',
        'zoho.com',
        'aol.com',
        'mail.com',
        'gmx.com',
        'yandex.com',
    ];

    /**
     * @return list<string|Closure>
     */
    public static function required(int $max = 255): array
    {
        return ['required', self::RULE, "max:{$max}", self::rejectCommonProviderTypos()];
    }

    /**
     * @return list<string|Closure>
     */
    public static function nullable(int $max = 255): array
    {
        return ['nullable', self::RULE, "max:{$max}", self::rejectCommonProviderTypos()];
    }

    /**
     * Validation messages for a given field name (email, admin_email, …).
     *
     * @return array<string, string>
     */
    public static function messages(string $attribute = 'email'): array
    {
        return [
            "{$attribute}.email" => self::MESSAGE,
        ];
    }

    /**
     * Reject domains that look like typos of popular email providers
     * (e.g. tdfcfdd@gmailll.com → suggests gmail.com).
     */
    private static function rejectCommonProviderTypos(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! is_string($value) || ! str_contains($value, '@')) {
                return;
            }

            $domain = strtolower((string) substr($value, (int) strrpos($value, '@') + 1));

            if ($domain === '' || in_array($domain, self::COMMON_PROVIDERS, true)) {
                return;
            }

            foreach (self::COMMON_PROVIDERS as $provider) {
                if ($domain[0] !== $provider[0]) {
                    continue;
                }

                if (abs(strlen($domain) - strlen($provider)) > 2) {
                    continue;
                }

                $distance = levenshtein($domain, $provider);

                if ($distance > 0 && $distance <= 2) {
                    $fail(self::MESSAGE.' Did you mean @'.$provider.'?');

                    return;
                }
            }
        };
    }
}
