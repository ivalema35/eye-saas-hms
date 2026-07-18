<?php

/**
 * PlatformAdmin.php
 *
 * PURPOSE: Super Admin user model.
 *          Platform ke admin users ko represent karta hai.
 *          Laravel auth guard 'superadmin' use karta hai ye model.
 *
 * TENANT-SCOPED: NO (Platform-level model)
 * TABLE: platform_admins
 * GUARD: superadmin
 */

namespace App\Models\Platform;

use App\Support\EmailRules;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class PlatformAdmin extends Authenticatable
{
    use HasFactory, HasApiTokens, Notifiable;

    protected $table = 'platform_admins';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'last_login_at',
        'last_login_ip',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'last_login_at' => 'datetime',
        'password' => 'hashed',
    ];

    protected function email(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => $value !== null && $value !== ''
                ? EmailRules::normalize($value)
                : $value,
        );
    }
}
