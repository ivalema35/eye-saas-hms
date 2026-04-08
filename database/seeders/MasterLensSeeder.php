<?php

namespace Database\Seeders;

use App\Models\Hospital\MasterLens;
use App\Models\Platform\Tenant;
use Illuminate\Database\Seeder;

class MasterLensSeeder extends Seeder
{
    public function run(): void
    {
        $lensValues = [
            'Absorbed Cataract',
            'AC/PC IOL',
            'ACIOL',
            'Aphakia',
            'ASC',
            'ASC+PSC',
            'Blue dot cataract',
            'Brown cataract',
            'Clear',
            'CLO',
            'Coloboma',
            'Complicated pseudophakia',
            'Congenital',
            'Cortical cataract',
            'Dense cataract',
            'Dense PSC',
            'Developmental',
            'Dot PSC',
            'Early PSC',
            'Grey Reflex',
            'Hyper mature cataract',
            'Hypermature - Morgagnian',
            'Hypermature - Sclerotic',
            'IMC',
            'IMC (NS++)',
            'IMC + Cortical',
            'IMC + PSC',
            'IMC + PSC + ASC',
            'IOL donesis',
            'Mature cataract',
            'Near mature cataract',
            'PCO',
            'Phacodonesis',
            'Phthisis',
            'Pigments on IOL',
            'Pigments on Lens',
            'PPC',
            'PSC',
            'Pseudophakia',
            'Pseudophakia MF',
            'PXF',
            'Rosette',
            'Subluxated',
            'Subluxated IOL',
            'Traumatic',
            'Uveitic cataract',
            'Weak Zonules',
        ];

        $tenants = Tenant::query()->get();

        MasterLens::unguarded(function () use ($tenants, $lensValues): void {
            foreach ($tenants as $tenant) {
                foreach ($lensValues as $value) {
                    MasterLens::query()->updateOrCreate([
                        'tenant_id' => $tenant->id,
                        'value' => $value,
                    ]);
                }
            }
        });

        $this->command?->info('Lens values seeded successfully for all tenants!');
    }
}
