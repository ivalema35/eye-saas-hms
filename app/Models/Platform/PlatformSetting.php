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

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

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
        if ($this->is_encrypted && $value) {
            return Crypt::decryptString($value);
        }

        return $value;
    }

    /** Encrypted save karo agar is_encrypted = true */
    public function setValueAttribute($value): void
    {
        if ($this->is_encrypted && $value) {
            $this->attributes['value'] = Crypt::encryptString($value);
        } else {
            $this->attributes['value'] = $value;
        }
    }

    /** Key se setting dhundo */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();

        return $setting ? $setting->value : $default;
    }
}
