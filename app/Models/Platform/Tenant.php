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

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Platform\Subscription;

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
        'state',
        'logo_path',
        'status',
        'trial_ends_at',
        'setup_completed_at',
        'is_setup_done',
    ];

    protected $casts = [
        'trial_ends_at'      => 'datetime',
        'setup_completed_at' => 'datetime',
        'is_setup_done'      => 'boolean',
    ];

    /** Active subscription for this tenant */
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
        return $this->hasMany(\App\Models\Hospital\HospitalUser::class);
    }

    /** Check if tenant has active/trial access */
    public function hasAccess(): bool
    {
        return in_array($this->status, ['trial', 'active', 'grace']);
    }
}
