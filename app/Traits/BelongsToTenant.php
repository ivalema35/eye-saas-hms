<?php

/**
 * BelongsToTenant.php
 *
 * PURPOSE: Multi-tenant data isolation trait.
 *          Ye trait jo bhi model use karega, uske saare queries
 *          automatically current tenant ke liye filter ho jayenge.
 *
 * HOW TO USE:
 *   class Patient extends Model {
 *       use BelongsToTenant;  // Bas ye ek line!
 *   }
 *
 * EFFECT:
 *   Patient::all() automatically ban jata hai:
 *   SELECT * FROM patients WHERE tenant_id = {currentTenantId}
 *
 * WARNING: Agar koi model hospital data store karta hai aur
 *          ye trait nahi lagata, to data leak ho sakta hai!
 *
 * EXCEPTIONS: Platform-level models (Tenant, Payment, PlatformAdmin)
 *             ye trait use NAHI karte.
 */

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Models\Platform\Tenant;

trait BelongsToTenant
{
    /**
     * Model boot hone pe global scope lagao.
     * Ye automatically chalta hai jab bhi model use hoti hai.
     */
    public static function bootBelongsToTenant(): void
    {
        // Global scope: har query me tenant_id filter
        static::addGlobalScope('tenant', function (Builder $query) {
            $tenantId = static::resolveTenantIdFromContext();

            // Agar tenant context set nahi hai (e.g., console commands),
            // to scope mat lagao — warna sab records chup jayenge
            if ($tenantId) {
                $query->where(
                    (new static)->getTable() . '.tenant_id',
                    $tenantId
                );
            }
        });

        // Naya record create karte waqt tenant_id auto-set karo
        static::creating(function (Model $model) {
            $tenantId = static::resolveTenantIdFromContext();
            if ($tenantId && empty($model->tenant_id)) {
                $model->tenant_id = $tenantId;
            }
        });
    }

    private static function resolveTenantIdFromContext(): ?int
    {
        if (app()->bound('tenant')) {
            return (int) app('tenant')->id;
        }

        if (app()->runningInConsole()) {
            return null;
        }

        $slug = request()->route('slug') ?: request()->segment(1);

        if (! $slug) {
            return null;
        }

        $tenant = Tenant::query()->where('slug', $slug)->first();

        if (! $tenant) {
            return null;
        }

        app()->instance('tenant', $tenant);

        return (int) $tenant->id;
    }

    /**
     * Tenant relation — agar kabhi zaroorat pade.
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Kabhi kabhi Super Admin ko without scope query karni hoti hai.
     * Is method se global scope hata sakte hain.
     *
     * Usage: Patient::withoutTenantScope()->where(...)->get()
     */
    public static function withoutTenantScope(): Builder
    {
        return static::withoutGlobalScope('tenant');
    }
}
