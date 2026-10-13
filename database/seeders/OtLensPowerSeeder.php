<?php

namespace Database\Seeders;

use App\Models\Platform\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Backfills the Lens Power master (+10.00D to +30.00D, 0.50D steps) for
 * every existing tenant. For new tenants this is handled automatically by
 * SeedTenantDefaultMasters; run this standalone to backfill hospitals that
 * were created before that job existed.
 *
 * Usage: php artisan db:seed --class=OtLensPowerSeeder
 */
class OtLensPowerSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = Tenant::query()->get();
        $ts = now();

        foreach ($tenants as $tenant) {
            $rows = [];

            for ($power = 10.00; $power <= 30.00; $power += 0.50) {
                $rows[] = [
                    'tenant_id' => $tenant->id,
                    'power' => number_format($power, 2),
                    'is_favourite' => $power >= 18.00 && $power <= 24.00,
                    'is_active' => true,
                    'created_at' => $ts,
                    'updated_at' => $ts,
                ];
            }

            DB::table('ot_lens_powers')->upsert(
                $rows,
                ['tenant_id', 'power'],
                ['is_favourite', 'is_active', 'updated_at']
            );
        }

        $this->command?->info('Lens Power values (+10.00D to +30.00D) seeded for all tenants!');
    }
}
