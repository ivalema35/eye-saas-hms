<?php

namespace Database\Seeders;

use App\Models\Hospital\MasterDisc;
use App\Models\Platform\Tenant;
use Illuminate\Database\Seeder;

class MasterDiscSeeder extends Seeder
{
    public function run(): void
    {
        $discValues = [
            '0.2 CDR',
            '0.3 CDR',
            '0.4 CDR',
            '0.5 CDR',
            '0.6 CDR',
            '0.7 CDR',
            '0.8 CDR',
            '0.9 CDR',
            'Normal',
            'Optic atrophy',
            'Pale NRR',
            'Tilted disc',
        ];

        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            MasterDisc::unguarded(function () use ($tenant, $discValues): void {
                foreach ($discValues as $value) {
                    MasterDisc::updateOrCreate(
                        [
                            'tenant_id' => $tenant->id,
                            'value' => $value,
                        ]
                    );
                }
            });
        }

        $this->command?->info('Disc values seeded successfully for all tenants!');
    }
}
