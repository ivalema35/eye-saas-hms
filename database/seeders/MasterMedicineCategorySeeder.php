<?php

namespace Database\Seeders;

use App\Models\Platform\MasterMedicineCategory;
use Illuminate\Database\Seeder;

class MasterMedicineCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Antibiotic',
            'Antibiotic + Steroid',
            'Steroid',
            'NSAID',
            'Lubricant / Artificial Tears',
            'Anti-Allergic',
            'Anti-Glaucoma',
            'Mydriatic',
            'Cycloplegic',
            'Miotic',
            'Anti-Viral',
            'Anti-Fungal',
            'Diagnostic Agent',
            'Local Anesthetic',
            'Vitamin & Supplement',
            'OT / Surgical Medicine',
            'Others',
        ];

        foreach ($categories as $category) {
            MasterMedicineCategory::query()->firstOrCreate(['name' => $category]);
        }

        $this->command?->info('Global master medicine categories seeded successfully!');
    }
}
