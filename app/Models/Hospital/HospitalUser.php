<?php

/**
 * HospitalUser.php
 *
 * PURPOSE: UNIFIED hospital user model â€” ek model sabke liye.
 *          hospital_admin, doctor, reception, ot_receptionist,
 *          accountant, ot_doctor, ot_assistant â€” sab ek hi table se.
 *
 * REPLACES: HospitalAdmin + Doctor + Reception + OtStaff (4 models)
 *
 * GUARD: hospital_user
 * TABLE: hospital_users
 * TENANT-SCOPED: YES (BelongsToTenant trait)
 *
 * ROLE RESOLUTION:
 *   User ka role role_id se determine hota hai â€” NOT by model class.
 *   Guard 'hospital_user' â€” ek hi guard sab ke liye.
 *
 * PERMISSION CHECK:
 *   $user->role->is_super === true  â†’ Hospital Admin, bypass all checks
 *   Otherwise                       â†’ RolePermissionService::can() use karo
 */

namespace App\Models\Hospital;

use App\Models\Platform\Tenant;
use App\Models\Role\Role;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\HasApiTokens;

class HospitalUser extends Authenticatable
{
    use BelongsToTenant, HasApiTokens, Notifiable, SoftDeletes;

    protected $table = 'hospital_users';

    protected $fillable = [
        'tenant_id',
        'role_id',
        'name',
        'email',
        'password',
        'contact',
        'status',
        'doctor_type',       // Only for Doctor role: primary | secondary
        'doctor_prefix',     // Only for Doctor role: 2-5 char prefix for daily serial (e.g. JP)
        'foc_permission',    // Only for Doctor role: boolean
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'foc_permission' => 'boolean',
        'last_login_at' => 'datetime',
        'password' => 'hashed',
    ];

    // â”€â”€ Relationships â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // â”€â”€ Password Reset â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    /**
     * Send the password reset notification with branded email.
     */
    public function sendPasswordResetNotification($token): void
    {
        $slug = $this->tenant?->slug ?? request()->route('slug');
        $url = route('hospital.password.reset', [
            'slug' => $slug,
            'token' => $token,
        ]).'?email='.urlencode($this->email);

        Mail::send('emails.hospital-reset-password', ['url' => $url], function ($message) {
            $message->to($this->email, $this->name)
                ->subject('Reset Your Password â€” '.config('app.name'));
        });
    }

    // â”€â”€ Helper Methods â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    /**
     * Is this user a Hospital Admin (super role â€” bypasses all permission checks)?
     */
    public function isSuperUser(): bool
    {
        // Role loaded nahi hai to load karo (without tenant scope conflict)
        if (! $this->relationLoaded('role') && $this->role_id !== null) {
            $this->load('role');
        }

        return $this->role?->is_super === true;
    }

    /**
     * Get role slug shorthand for UI display.
     */
    public function getRoleSlugAttribute(): ?string
    {
        return $this->role?->slug;
    }

    /**
     * Is this user active?
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Scope: Only active users.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
