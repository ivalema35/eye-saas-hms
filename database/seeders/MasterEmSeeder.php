<?php

namespace Database\Seeders;

use App\Models\Hospital\MasterEm;
use App\Models\Platform\Tenant;
use Illuminate\Database\Seeder;

class MasterEmSeeder extends Seeder
{
    public function run(): void
    {
        $emValues = [
            '3rd nerve palsy',
            '6th nerve palsy',
            'Alt. ESO',
            'Alt. EXO',
            'Esotropia',
            'Exotropia',
            'Full',
            'LR palsy',
            'MR palsy',
            'Nystagmus',
            'Restricted',
            'SR under action',
            'Total ophthalmoplegia',
        ];

        $tenants = Tenant::query()->get();

        MasterEm::unguarded(function () use ($tenants, $emValues): void {
            foreach ($tenants as $tenant) {
                foreach ($emValues as $value) {
                    MasterEm::query()->updateOrCreate([
                        'tenant_id' => $tenant->id,
                        'value' => $value,
                    ]);
                }
            }
        });

        $this->command?->info('E/M (Extraocular Movements) values seeded successfully for all tenants!');
    }
}
