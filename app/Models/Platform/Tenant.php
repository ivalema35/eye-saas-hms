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
use App\Support\EmailRules;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use SoftDeletes;

    protected $table = 'tenants';

    protected $fillable = [
        'name',
        'slug',
        'hospital_code',
        'admin_name',
        'admin_email',
        'admin_phone',
        'city',
        'district',
        'state',
        'country',
        'timezone',
        'is_timezone_override',
        'currency_code',
        'currency_symbol',
        'is_currency_override',
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
        'is_currency_override' => 'boolean',
    ];

    protected function adminEmail(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => $value !== null && $value !== ''
                ? EmailRules::normalize($value)
                : $value,
        );
    }

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

    /** Latest date the hospital may still use the product (trial, subscription, or grace). */
    public function planAccessEndsAt(): ?\Illuminate\Support\Carbon
    {
        $dates = [];

        if ($this->trial_ends_at) {
            $dates[] = $this->trial_ends_at->copy();
        }

        $subscription = $this->relationLoaded('subscriptions')
            ? $this->subscriptions->sortByDesc('id')->first()
            : $this->subscriptions()->latest()->first();

        if ($subscription) {
            if ($subscription->ends_at) {
                $dates[] = \Illuminate\Support\Carbon::parse($subscription->ends_at);
            }
            if ($subscription->grace_ends_at) {
                $dates[] = \Illuminate\Support\Carbon::parse($subscription->grace_ends_at);
            }
        }

        if ($dates === []) {
            return null;
        }

        return collect($dates)->sortByDesc(fn ($d) => $d->getTimestamp())->first();
    }

    public function isPlanExpired(): bool
    {
        if ($this->status === 'pending') {
            return false;
        }

        $end = $this->planAccessEndsAt();
        if ($end) {
            return now()->greaterThan($end);
        }

        return in_array($this->status, ['expired', 'inactive'], true);
    }

    /** Status shown in SuperAdmin (Expired when plan dates have passed). */
    public function displayStatus(): string
    {
        if ($this->status === 'pending') {
            return 'pending';
        }

        if ($this->status === 'suspended') {
            return 'suspended';
        }

        if ($this->isPlanExpired()) {
            return 'expired';
        }

        return (string) $this->status;
    }

    public function markExpiredIfNeeded(): void
    {
        if (in_array($this->status, ['suspended', 'pending'], true)) {
            return;
        }

        if ($this->isPlanExpired() && $this->status !== 'expired') {
            try {
                $this->update(['status' => 'expired']);
            } catch (\Throwable $e) {
                // Enum migrate pending — login still blocked via isPlanExpired()
            }
        }
    }

    /** Check if tenant has product access (login allowed). */
    public function hasAccess(): bool
    {
        if (in_array($this->status, ['suspended', 'pending'], true)) {
            return false;
        }

        return ! $this->isPlanExpired();
    }

    /** Same hospitals SuperAdmin shows as Trial or Grace (not expired / blocked). */
    public function scopePartnerVisible($query)
    {
        return $query->whereIn('status', ['trial', 'grace']);
    }

    /**
     * Hospitals that can currently log in (same rules as UnifiedLoginController).
     *
     * @return \Illuminate\Support\Collection<int, self>
     */
    public static function loginable(): \Illuminate\Support\Collection
    {
        return static::query()
            ->whereNotIn('status', ['suspended', 'pending'])
            ->with('subscriptions')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'status', 'trial_ends_at'])
            ->filter(fn (self $tenant) => $tenant->hasAccess())
            ->values();
    }
}
