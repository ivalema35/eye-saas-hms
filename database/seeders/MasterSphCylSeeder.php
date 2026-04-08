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
                MasterSphCyl::query()->updateOrCreate([
                    'tenant_id' => $tenant->id,
                    'type' => 'Positive',
                    'value' => '0',
                ]);

                for ($i = 0.25; $i <= 10.00; $i += 0.25) {
                    $value = number_format($i, 2);

                    MasterSphCyl::query()->updateOrCreate([
                        'tenant_id' => $tenant->id,
                        'type' => 'Positive',
                        'value' => $value,
                    ]);

                    MasterSphCyl::query()->updateOrCreate([
                        'tenant_id' => $tenant->id,
                        'type' => 'Negative',
                        'value' => $value,
                    ]);
                }
            }
        });

        $this->command?->info('SPH/CYL values (0.25 to 10.00) seeded successfully for all tenants!');
    }
}
