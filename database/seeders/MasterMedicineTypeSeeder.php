<?php

namespace Database\Seeders;

use App\Models\Platform\MasterMedicineType;
use Illuminate\Database\Seeder;

class MasterMedicineTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            'Tablet',
            'Capsule',
            'Syrup',
            'Suspension',
            'Injection',
            'Eye Drop',
            'Eye Ointment',
            'Eye Gel',
            'Eye Lotion',
            'Eye Wash',
            'Eye Solution',
            'Eye Suspension',
        ];

        foreach ($types as $type) {
            MasterMedicineType::query()->firstOrCreate(['name' => $type]);
        }

        $this->command?->info('Global master medicine types seeded successfully!');
    }
}
