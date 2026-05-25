<?php

namespace App\Services\Platform;

use App\Jobs\SeedTenantDefaultMasters;
use App\Jobs\SendWelcomeEmail;
use App\Models\Hospital\HospitalUser;
use App\Models\Platform\Tenant;
use App\Models\Role\Role;
use Carbon\Carbon;
use Database\Seeders\SystemRolesSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class TenantService
{
    public function createTenant(array $data): Tenant
    {
        // Dispatch master seeder AFTER transaction commits so:
        // 1. Tenant data is visible to queue workers (async drivers)
        // 2. A job failure doesn't roll back hospital creation (sync driver)
        $tenantId = null;

        $tenant = DB::transaction(function () use ($data, &$tenantId) {

            $trialDays = (int) env('SUBSCRIPTION_TRIAL_DAYS', 14);
            $trialEndsAt = Carbon::now()->addDays($trialDays);

            // 1. Tenant record create karo
            $tenant = Tenant::create([
                'name' => $data['hospital_name'],
                'slug' => $data['slug'],
                'admin_name' => $data['admin_name'],
                'admin_email' => $data['admin_email'],
                'admin_phone' => $data['admin_phone'],
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'status' => 'trial',
                'trial_ends_at' => $trialEndsAt,
                'is_setup_done' => false,
            ]);

            // 2. System roles + permissions seed karo (all 7 roles including OT)
            SystemRolesSeeder::seedForTenant($tenant->id);

            // FIX: withoutTenantScope() use karo —
            // Public registration context mein config('app.tenant_id') = 0 hota hai.
            // Role::query() pe BelongsToTenant scope WHERE tenant_id = 0 add karta hai,
            // jo explicit WHERE tenant_id = {newId} se conflict karta hai → NULL return.
            $adminRoleId = Role::withoutTenantScope()
                ->where('tenant_id', $tenant->id)
                ->where('slug', 'hospital_admin')
                ->value('id');

            if (! $adminRoleId) {
                Log::error("TenantService: hospital_admin role not found for tenant #{$tenant->id} after seeding!");
            }

            // 3. Hospital Admin user banao
            HospitalUser::create([
                'tenant_id' => $tenant->id,
                'role_id' => $adminRoleId,
                'name' => $data['admin_name'],
                'email' => $data['admin_email'],
                'password' => Hash::make($data['password']),
                'contact' => $data['admin_phone'] ?? null,
                'status' => 'active',
            ]);

            $tenantId = $tenant->id;

            Log::info("New tenant created: #{$tenant->id} ({$tenant->slug}) — trial until {$trialEndsAt->toDateString()}");

            return $tenant;
        });

        // Dispatch OUTSIDE transaction — tenant is committed and visible to job
        SendWelcomeEmail::dispatch($tenant);
        SeedTenantDefaultMasters::dispatch($tenantId);

        return $tenant;
    }

    public function activate(Tenant $tenant): void
    {
        $tenant->update(['status' => 'active']);
        Log::info("Tenant activated: #{$tenant->id} ({$tenant->slug})");
    }

    public function suspend(Tenant $tenant): void
    {
        $tenant->update(['status' => 'suspended']);
        Log::warning("Tenant suspended: #{$tenant->id} ({$tenant->slug})");
    }

    public function extendGrace(Tenant $tenant, int $days): void
    {
        $subscription = $tenant->subscriptions()->latest()->first();
        if ($subscription) {
            $newEnd = Carbon::parse($subscription->ends_at)->addDays($days);
            $subscription->update(['ends_at' => $newEnd]);
        }

        $tenant->update(['status' => 'grace']);
        Log::info("Tenant #{$tenant->id} grace extended by {$days} days");
    }
}
