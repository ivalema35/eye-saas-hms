<?php

namespace Database\Seeders;

use App\Models\Hospital\MasterSphCyl;
use App\Models\Platform\Tenant;
use Illuminate\Database\Seeder;

class MasterSphCylSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = Tenant::query()->get();

        MasterSphCyl::unguarded(function () use ($tenants): void {
            foreach ($tenants as $tenant) {
                for ($i = 0.25; $i <= 10.00; $i += 0.25) {
                    $value = number_format($i, 2);
                    MasterSphCyl::query()->updateOrCreate(
                        ['tenant_id' => $tenant->id, 'value' => $value],
                        ['is_seeded' => true],
                    );
                }
            }
        });

        $this->command?->info('SPH/CYL values (0.25 to 10.00) seeded successfully for all tenants!');
    }
}
