<?php

namespace Database\Seeders;

use App\Models\Hospital\MasterConj;
use App\Models\Platform\Tenant;
use Illuminate\Database\Seeder;

class MasterConjSeeder extends Seeder
{
    public function run(): void
    {
        $conjValues = [
            'Allergic conjunctivitis',
            'CCC++',
            'Chemosis',
            'Concretions',
            'Conj. Congestion',
            'Conj. Growth',
            'Conj. suture',
            'Conjunctival cyst',
            'Conjunctivitis (Bac)',
            'Conjunctivitis (Viral)',
            'Cyst',
            'Dermoid',
            'Dry eye',
            'Early n. pterygium',
            'Episcleritis',
            'Follicles',
            'Inflammed pinguecula',
            'Inflammed pterygium',
            'Mole',
            'N. pterygium scar',
            'N+T pterygium',
            'Nevus',
            'Oedema',
            'Papillae',
            'Pinguecula',
            'Pterygium',
            'Recurrent n. pterygium',
            'Recurrent t. pterygium',
            'Redness',
            'Scarring',
            'SCH mild',
            'Symblepharon',
            'T. Pterygium',
            'T. Ptr scar',
            'VKC',
        ];

        $tenants = Tenant::query()->get();

        MasterConj::unguarded(function () use ($tenants, $conjValues): void {
            foreach ($tenants as $tenant) {
                foreach ($conjValues as $value) {
                    MasterConj::query()->updateOrCreate([
                        'tenant_id' => $tenant->id,
                        'value' => $value,
                    ]);
                }
            }
        });

        $this->command?->info('Conjunctiva (Conj) values seeded successfully for all tenants!');
    }
}
