<?php

namespace Database\Seeders;

use App\Models\Hospital\MasterAxis;
use App\Models\Platform\Tenant;
use Illuminate\Database\Seeder;

class MasterAxisSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = Tenant::query()->get();

        MasterAxis::unguarded(function () use ($tenants): void {
            foreach ($tenants as $tenant) {
                for ($i = 5; $i <= 180; $i += 5) {
                    MasterAxis::query()->updateOrCreate([
                        'tenant_id' => $tenant->id,
                        'value' => $i.'°',
                    ]);
                }
            }
        });

        $this->command?->info('Axis values (5° to 180°) seeded successfully for all tenants!');
    }
}
