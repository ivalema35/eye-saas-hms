<?php

namespace Database\Seeders;

use App\Models\Hospital\MasterLid;
use App\Models\Platform\Tenant;
use Illuminate\Database\Seeder;

class MasterLidSeeder extends Seeder
{
    public function run(): void
    {
        $lidValues = [
            'Blepharitis',
            'Chalazion',
            'Cyst',
            'Dermoid',
            'Dystichiasis',
            'Ectropion',
            'Epiblepharon',
            'Hemangioma',
            'Hordeolum',
            'L/L Stye',
            'Lid Tear',
            'Madarosis',
            'Mole',
            'Poliosis',
            'Ptosis',
            'Stye',
            'Swelling',
            'Tear',
            'Trauma',
            'Trichiasis',
            'U/L Stye',
            'Xanthelasma',
        ];

        $tenants = Tenant::query()->get();

        MasterLid::unguarded(function () use ($tenants, $lidValues): void {
            foreach ($tenants as $tenant) {
                foreach ($lidValues as $value) {
                    MasterLid::query()->updateOrCreate([
                        'tenant_id' => $tenant->id,
                        'value' => $value,
                    ]);
                }
            }
        });

        $this->command?->info('Lid values seeded successfully for all tenants!');
    }
}
