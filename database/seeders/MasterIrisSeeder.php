<?php

namespace Database\Seeders;

use App\Models\Hospital\MasterIris;
use App\Models\Platform\Tenant;
use Illuminate\Database\Seeder;

class MasterIrisSeeder extends Seeder
{
    public function run(): void
    {
        $irisValues = [
            'Adherent leucoma',
            'Atrophy',
            'Coloboma',
            'Cyst',
            'Heterochromia',
            'Iridodialysis',
            'Irregular',
            'Nevus',
            'Nodule',
            'Normal',
            'NVI',
            'PEX iritis',
            'PI',
            'Pigment',
            'Prolapse',
            'P. synechia',
            'Rubeosis',
            'SI',
            'Synechia',
            'YAG PI',
        ];

        $tenants = Tenant::query()->get();

        MasterIris::unguarded(function () use ($tenants, $irisValues): void {
            foreach ($tenants as $tenant) {
                foreach ($irisValues as $value) {
                    MasterIris::query()->updateOrCreate([
                        'tenant_id' => $tenant->id,
                        'value' => $value,
                    ]);
                }
            }
        });

        $this->command?->info('Iris values seeded successfully for all tenants!');
    }
}
