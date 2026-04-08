<?php

/**
 * HospitalSetting.php
 *
 * PURPOSE: Per-hospital configuration key-value store.
 *          Har hospital ki apni settings hoti hain yahan.
 *          BelongsToTenant: cross-hospital isolation.
 *
 * TENANT-SCOPED: YES (BelongsToTenant trait)
 * TABLE: hospital_settings
 */

namespace App\Models\Hospital;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class HospitalSetting extends Model
{
    use BelongsToTenant;

    protected $table = 'hospital_settings';

    protected $fillable = [
        'tenant_id',
        'key',
        'value',
    ];

    /** Key se current tenant ki setting lo */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();

        return $setting ? $setting->value : $default;
    }

    /** Setting set karo â€” upsert logic */
    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }
}
