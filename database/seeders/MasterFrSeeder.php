<?php

namespace Database\Seeders;

use App\Models\Hospital\MasterFr;
use App\Models\Platform\Tenant;
use Illuminate\Database\Seeder;

class MasterFrSeeder extends Seeder
{
    public function run(): void
    {
        $frValues = [
            'ARMD',
            'CME',
            'CSR',
            'DME',
            'FR dull',
            'Lasered PDR',
            'Macular scar',
            'Mild NPDR',
            'Moderate NPDR',
            'Myopic fundus',
            'Normal',
            'PDR',
            'RP',
            'RPE defect',
            'Severe NPDR',
            'VH',
        ];

        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            MasterFr::unguarded(function () use ($tenant, $frValues): void {
                foreach ($frValues as $value) {
                    MasterFr::updateOrCreate(
                        [
                            'tenant_id' => $tenant->id,
                            'value' => $value,
                        ]
                    );
                }
            });
        }

        $this->command?->info('F/R (Fundus) values seeded successfully for all tenants!');
    }
}
