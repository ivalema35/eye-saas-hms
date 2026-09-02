<?php

/**
 * PlatformSetting.php
 *
 * PURPOSE: Platform configuration key-value store.
 *          Razorpay keys, mail settings aur baaki platform config yahan.
 *
 * TENANT-SCOPED: NO (Platform-level model)
 * TABLE: platform_settings
 */

namespace App\Models\Platform;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class PlatformSetting extends Model
{
    protected $table = 'platform_settings';

    protected $fillable = [
        'key',
        'value',
        'is_encrypted',
        'group',
    ];

    protected $casts = [
        'is_encrypted' => 'boolean',
    ];

    /** Decrypted value return karo agar encrypted hai */
    public function getValueAttribute($value): ?string
    {
        if (! $this->is_encrypted || ! $value) {
            return $value;
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            Log::warning("PlatformSetting [{$this->key}]: could not decrypt — re-save this value in Super Admin settings.");

            return null;
        }
    }

    /** Encrypted save karo agar is_encrypted = true */
    public function setValueAttribute($value): void
    {
        if ($this->is_encrypted && $value !== null && $value !== '') {
            $this->attributes['value'] = Crypt::encryptString((string) $value);
        } else {
            $this->attributes['value'] = $value;
        }
    }

    /** Key se setting dhundo (decrypted when applicable). */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();

        return $setting ? ($setting->value ?? $default) : $default;
    }

    /** Check if a key has a stored value without decrypting. */
    public static function has(string $key): bool
    {
        $value = static::where('key', $key)->value('value');

        return is_string($value) && trim($value) !== '';
    }

    /**
     * Save a setting — sets is_encrypted before value to avoid plain-text + encrypted flag mismatch.
     */
    public static function set(string $key, mixed $value, bool $encrypted = false, string $group = 'general'): self
    {
        $setting = static::firstOrNew(['key' => $key]);
        $setting->group = $group;
        $setting->is_encrypted = $encrypted;

        if ($encrypted && $value !== null && $value !== '') {
            $setting->attributes['value'] = Crypt::encryptString((string) $value);
        } else {
            $setting->attributes['value'] = $value;
        }

        $setting->save();

        return $setting;
    }
}
