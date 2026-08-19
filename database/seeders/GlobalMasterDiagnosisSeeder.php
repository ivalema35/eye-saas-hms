<?php

namespace Database\Seeders;

use App\Models\Platform\GlobalMasterDiagnosis;
use Illuminate\Database\Seeder;

class GlobalMasterDiagnosisSeeder extends Seeder
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

        foreach ($diagnosisValues as $value) {
            GlobalMasterDiagnosis::query()->firstOrCreate(['value' => $value]);
        }

        $this->command?->info('Global master diagnosis values seeded successfully!');
    }
}
