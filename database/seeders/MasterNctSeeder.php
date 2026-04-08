<?php

namespace Database\Seeders;

use App\Models\Hospital\MasterNct;
use App\Models\Platform\Tenant;
use Illuminate\Database\Seeder;

class MasterNctSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = Tenant::query()->get();

        MasterNct::unguarded(function () use ($tenants): void {
            foreach ($tenants as $tenant) {
                for ($i = 1; $i <= 60; $i++) {
                    MasterNct::query()->updateOrCreate([
                        'tenant_id' => $tenant->id,
                        'value' => (string) $i,
                    ]);
                }

                foreach (['Soft', 'High', 'Low'] as $value) {
                    MasterNct::query()->updateOrCreate([
                        'tenant_id' => $tenant->id,
                        'value' => $value,
                    ]);
                }
            }
        });

        $this->command?->info('NCT values (1 to 60 + Generic) seeded successfully for all tenants!');
    }
}
