<?php

namespace Database\Seeders;

use App\Models\Hospital\MasterPupil;
use App\Models\Platform\Tenant;
use Illuminate\Database\Seeder;

class MasterPupilSeeder extends Seeder
{
    public function run(): void
    {
        $pupilValues = [
            'Anterior synechia',
            'Atrophy',
            'Dilated',
            'Fixed',
            'Irregular',
            'Mid dilated',
            'Mid dilated (ATRO)',
            'Miotic',
            'Miotic NR>L',
            'Mydriasis 3mm',
            'Mydriasis 4mm',
            'Mydriasis NR>L',
            'Mydriasis (Full)',
            'Mydriasis eccentric',
            'Mydriasis SR>L',
            'Normal',
            'Optic capture',
            'PEX',
            'Post. synechia',
            'RAPD GR1',
            'RAPD GR2',
            'RAPD GR3',
            'RAPD GR4',
            'A_RRRL',
            'SR>L',
            'SR>OVAL',
            'Synechia',
            'Updrawn pupil',
        ];

        $tenants = Tenant::query()->get();

        MasterPupil::unguarded(function () use ($tenants, $pupilValues): void {
            foreach ($tenants as $tenant) {
                foreach ($pupilValues as $value) {
                    MasterPupil::query()->updateOrCreate([
                        'tenant_id' => $tenant->id,
                        'value' => $value,
                    ]);
                }
            }
        });

        $this->command?->info('Pupil values seeded successfully for all tenants!');
    }
}
