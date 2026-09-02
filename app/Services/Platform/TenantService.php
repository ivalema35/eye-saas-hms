<?php

namespace App\Services\Platform;

use App\Jobs\SeedTenantDefaultMasters;
use App\Jobs\SendWelcomeEmail;
use App\Models\Hospital\HospitalUser;
use App\Models\Platform\Tenant;
use App\Models\Role\Role;
use App\Services\Platform\CurrencyService;
use App\Services\Platform\TimezoneService;
use Carbon\Carbon;
use Database\Seeders\SystemRolesSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class TenantService
{
    public function __construct(
        private readonly TimezoneService $timezoneService,
        private readonly CurrencyService $currencyService,
    ) {}

    public function createTenant(array $data): Tenant
    {
        // Dispatch master seeder AFTER transaction commits so:
        // 1. Tenant data is visible to queue workers (async drivers)
        // 2. A job failure doesn't roll back hospital creation (sync driver)
        $tenantId = null;

        $tenant = DB::transaction(function () use ($data, &$tenantId) {

            $needsApproval = empty($data['skip_approval']);

            $trialDays = platform_trial_days();
            $trialEndsAt = $needsApproval ? null : Carbon::now()->addDays($trialDays);

            // 1. Tenant record create karo
            $country  = $data['country'] ?? null;
            $timezone = $country
                ? $this->timezoneService->getTimezoneForCountry($country)
                : 'UTC';
            $currency = $country
                ? $this->currencyService->getCurrencyForCountry($country)
                : [
                    'code' => config('app.platform_currency_code', 'INR'),
                    'symbol' => config('app.platform_currency_symbol', '₹'),
                ];

            $tenant = Tenant::create([
                'name' => $data['hospital_name'],
                'slug' => $data['slug'],
                'hospital_code' => strtoupper($data['hospital_code']),
                'admin_name' => $data['admin_name'],
                'admin_email' => $data['admin_email'],
                'admin_phone' => $data['admin_phone'],
                'country'  => $country,
                'state'    => $data['state'] ?? null,
                'district' => $data['district'] ?? null,
                'city'     => $data['city'] ?? null,
                'timezone' => $timezone,
                'is_timezone_override' => false,
                'currency_code' => $currency['code'],
                'currency_symbol' => $currency['symbol'],
                'is_currency_override' => false,
                'status' => $needsApproval ? 'pending' : 'trial',
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
                'original_password' => $data['password'],
                'contact' => $data['admin_phone'] ?? null,
                'status' => 'active',
            ]);

            $tenantId = $tenant->id;

            Log::info("New tenant created: #{$tenant->id} ({$tenant->slug}) — status {$tenant->status}");

            return $tenant;
        });

        // Dispatch OUTSIDE transaction — tenant is committed and visible to job
        if (empty($data['skip_approval'])) {
            SeedTenantDefaultMasters::dispatch($tenantId);
        } else {
            SendWelcomeEmail::dispatch($tenant);
            SeedTenantDefaultMasters::dispatch($tenantId);
        }

        return $tenant;
    }

    public function approveRegistration(Tenant $tenant): void
    {
        $tenant->refresh();

        if ($tenant->status !== 'pending') {
            throw new \RuntimeException("Hospital '{$tenant->name}' is not waiting for approval.");
        }

        $trialDays = platform_trial_days();
        $updated = $tenant->update([
            'status' => 'trial',
            'trial_ends_at' => Carbon::now()->addDays($trialDays),
        ]);

        if (! $updated) {
            throw new \RuntimeException("Could not approve hospital '{$tenant->name}'. Please try again.");
        }

        $tenant->refresh();

        SendWelcomeEmail::dispatch($tenant);
        Log::info("Tenant registration approved: #{$tenant->id} ({$tenant->slug}) — status {$tenant->status}");
    }

    public function rejectRegistration(Tenant $tenant): void
    {
        $tenant->update(['status' => 'inactive']);
        Log::info("Tenant registration rejected: #{$tenant->id} ({$tenant->slug})");
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
            $baseEnd = $subscription->ends_at && Carbon::parse($subscription->ends_at)->isFuture()
                ? Carbon::parse($subscription->ends_at)
                : now();
            $newEnd = $baseEnd->copy()->addDays($days);
            $subscription->update(['ends_at' => $newEnd]);
            $tenant->update(['status' => 'grace']);
        } else {
            $base = $tenant->trial_ends_at && Carbon::parse($tenant->trial_ends_at)->isFuture()
                ? Carbon::parse($tenant->trial_ends_at)
                : now();
            $newTrialEnd = $base->copy()->addDays($days);
            $tenant->update(['status' => 'grace', 'trial_ends_at' => $newTrialEnd]);
        }
        Log::info("Tenant #{$tenant->id} grace extended by {$days} days");
    }
}
