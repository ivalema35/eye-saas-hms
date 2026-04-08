<?php

namespace Database\Seeders;

use App\Models\Hospital\MasterSac;
use App\Models\Platform\Tenant;
use Illuminate\Database\Seeder;

class MasterSacSeeder extends Seeder
{
    public function run(): void
    {
        $sacValues = [
            'Acute DC',
            'Block',
            'Block with clear fluid',
            'Block with discharge',
            'Fistula',
            'Partially patent',
            'Patent',
            'Roplas +',
        ];

        $tenants = Tenant::query()->get();

        MasterSac::unguarded(function () use ($tenants, $sacValues): void {
            foreach ($tenants as $tenant) {
                foreach ($sacValues as $value) {
                    MasterSac::query()->updateOrCreate([
                        'tenant_id' => $tenant->id,
                        'value' => $value,
                    ]);
                }
            }
        });

        $this->command?->info('SAC (Lacrimal Sac) values seeded successfully for all tenants!');
    }
}
