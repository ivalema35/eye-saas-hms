<?php

namespace App\Support;

/**
 * Shared international phone validation (E.164-inspired).
 *
 * Allows an optional leading "+", then 7–15 digits.
 * Rejects letters, spaces, multiple "+", and other special characters.
 */
final class PhoneRules
{
    /** Laravel regex rule string */
    public const RULE = 'regex:/^\+?[0-9]{7,15}$/';

    /** Max stored length — matches DB varchar(15) columns */
    public const MAX = 15;

    public const MESSAGE = 'Please enter a valid phone number (7–15 digits, optional leading +).';

    /** HTML pattern attribute value (no delimiters) */
    public const HTML_PATTERN = '\+?[0-9]{7,15}';

    /**
     * @return list<string>
     */
    public static function required(int $max = self::MAX): array
    {
        return ['required', 'string', "max:{$max}", self::RULE];
    }

    /**
     * @return list<string>
     */
    public static function nullable(int $max = self::MAX): array
    {
        return ['nullable', 'string', "max:{$max}", self::RULE];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(string $attribute = 'phone'): array
    {
        return [
            "{$attribute}.regex" => self::MESSAGE,
        ];
    }
}
