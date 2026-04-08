<?php

namespace Database\Seeders;

use App\Models\Hospital\MasterDiagnosis;
use App\Models\Platform\Tenant;
use Illuminate\Database\Seeder;

class MasterDiagnosisSeeder extends Seeder
{
    public function run(): void
    {
        $diagnosisValues = [
            '3rd Nerve Palsy',
            '6th Nerve Palsy',
            'Allergic Conjunctivitis',
            'ARMD',
            'Astigmatism',
            'Bacterial Conjunctivitis',
            'Bilateral Pseudophakia',
            'Blepharitis',
            'BRVO',
            'CRVO',
            'Chalazion',
            'Chronic Simple Glaucoma',
            'Color Blindness',
            'Convergence Insufficiency',
            'Corneal Abrasion',
            'Corneal Ulcer',
            'Dacryocystitis',
            'Dry Eye Syndrome (DES)',
            'Episcleritis',
            'Esotropia',
            'Exotropia',
            'Foreign Body',
            'Glaucoma',
            'Hypermetropia',
            'Hypermature Cataract',
            'IMC with PSC',
            'Immature Cataract',
            'Iritis',
            'Keratitis',
            'Macular Edema',
            'Macular Hole',
            'Mature Cataract',
            'Myopia',
            'Myopic Fundus',
            'NPDR',
            'Optic Atrophy',
            'PDR',
            'Pinguecula',
            'Presbyopia',
            'Pseudophakia',
            'Pterygium',
            'Retinal Detachment',
            'Scleritis',
            'Stye',
            'Uveitis',
            'Vitreous Hemorrhage (VH)',
        ];

        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            MasterDiagnosis::unguarded(function () use ($tenant, $diagnosisValues): void {
                foreach ($diagnosisValues as $value) {
                    MasterDiagnosis::updateOrCreate(
                        [
                            'tenant_id' => $tenant->id,
                            'value' => $value,
                        ]
                    );
                }
            });
        }

        $this->command?->info('Diagnosis master values seeded successfully!');
    }
}
