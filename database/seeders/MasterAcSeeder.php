<?php

namespace Database\Seeders;

use App\Models\Hospital\MasterAc;
use App\Models\Platform\Tenant;
use Illuminate\Database\Seeder;

class MasterAcSeeder extends Seeder
{
    public function run(): void
    {
        $acValues = [
            'Cells +1',
            'Cells +2',
            'Cells +3',
            'Cells +4',
            'Deep AC',
            'Flare +1',
            'Flare +2',
            'Flare +3',
            'Flare +4',
            'Flat',
            'Fuch\'s',
            'Hyphema',
            'Hypopyon',
            'Inflammatory membrane',
            'Lens matter',
            'PAS',
            'PEX',
            'PI',
            'Pigment',
            'Pigments',
            'Quiet normal depth',
            'Shallow',
            'Shallow air bubble',
            'Silicon oil',
            'VIT',
            'Vit in AC',
        ];

        $tenants = Tenant::query()->get();

        MasterAc::unguarded(function () use ($tenants, $acValues): void {
            foreach ($tenants as $tenant) {
                foreach ($acValues as $value) {
                    MasterAc::query()->updateOrCreate([
                        'tenant_id' => $tenant->id,
                        'value' => $value,
                    ]);
                }
            }
        });

        $this->command?->info('AC (Anterior Chamber) values seeded successfully for all tenants!');
    }
}
