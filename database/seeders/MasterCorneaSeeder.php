<?php

namespace Database\Seeders;

use App\Models\Hospital\MasterCornea;
use App\Models\Platform\Tenant;
use Illuminate\Database\Seeder;

class MasterCorneaSeeder extends Seeder
{
    public function run(): void
    {
        $corneaValues = [
            'Abscess',
            'Adherent leucoma',
            'Arcus senilis',
            'BSK',
            'Central k\' opacity',
            'CL, k\' haze',
            'Clear',
            'Congestion',
            'Corneal FB',
            'DEG+++',
            'Degeneration',
            'Descemet folds',
            'DM detachment',
            'Dystrophy',
            'Epithelial defect',
            'EPK',
            'Foreign body',
            'K\' vascularization',
            'Keratic precipitates (KPs)',
            'Keratitis',
            'Oedema',
            'Opacity - nonsignificant',
            'Opacity - significant',
            'Perforation',
            'Pig. on endo.',
            'Scar',
            'Shield ulcer',
            'SK',
            'SPE',
            'Spheroidal deg',
            'SPK',
            'Suture opacity',
            'Tear',
            'Tear sealed',
            'Thinning',
            'Total k\' opacity',
            'Ulcer',
            'Ulcer - bacterial',
            'Ulcer - fungal',
            'Vascularisation',
        ];

        $tenants = Tenant::query()->get();

        MasterCornea::unguarded(function () use ($tenants, $corneaValues): void {
            foreach ($tenants as $tenant) {
                foreach ($corneaValues as $value) {
                    MasterCornea::query()->updateOrCreate([
                        'tenant_id' => $tenant->id,
                        'value' => $value,
                    ]);
                }
            }
        });

        $this->command?->info('Cornea values seeded successfully for all tenants!');
    }
}
