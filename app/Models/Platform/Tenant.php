<?php

/**
 * Tenant.php
 *
 * PURPOSE: Platform-level tenant model.
 *          Har registered hospital/clinic ki ek entry hogi.
 *          Ye sabse important platform model hai.
 *
 * TENANT-SCOPED: NO (Platform-level model, no BelongsToTenant trait)
 * TABLE: tenants
 */

namespace App\Models\Platform;

use App\Models\Hospital\HospitalUser;
use App\Models\Hospital\Patient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use SoftDeletes;

    protected $table = 'tenants';

    protected $fillable = [
        'name',
        'slug',
        'admin_name',
        'admin_email',
        'admin_phone',
        'city',
        'district',
        'state',
        'country',
        'timezone',
        'is_timezone_override',
        'logo_path',
        'status',
        'trial_ends_at',
        'setup_completed_at',
        'is_setup_done',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'setup_completed_at' => 'datetime',
        'is_setup_done' => 'boolean',
        'is_timezone_override' => 'boolean',
    ];

    /** Effective timezone — falls back to country default if not overridden */
    public function effectiveTimezone(): string
    {
        if ($this->timezone && $this->timezone !== 'UTC') {
            return $this->timezone;
        }
        return 'UTC';
    }

    /** Active subscription for this tenant */
    public function doctors()
    {
        return $this->hasMany(HospitalUser::class, 'tenant_id')
            ->whereHas('role', fn($q) => $q->where('slug', 'doctor'));
    }

    public function receptionists()
    {
        return $this->hasMany(HospitalUser::class, 'tenant_id')
            ->whereHas('role', fn($q) => $q->where('slug', 'receptionist'));
    }

    public function patients()
    {
        return $this->hasMany(Patient::class, 'tenant_id');
    }

    public function activeSubscription()
    {
        return $this->hasOne(Subscription::class)
            ->where('status', 'active')
            ->latest();
    }

    /** All subscriptions */
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    /** All payments */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /** Hospital staff users (all roles) */
    public function hospitalUsers()
    {
        return $this->hasMany(HospitalUser::class);
    }

    /** Check if tenant has active/trial access */
    public function hasAccess(): bool
    {
        return in_array($this->status, ['trial', 'active', 'grace']);
    }
}
