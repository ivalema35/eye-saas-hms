<?php

namespace Database\Seeders;

use App\Models\Platform\MasterDosage;
use Illuminate\Database\Seeder;

class MasterDosageSeeder extends Seeder
{
    public function run(): void
    {
        $dosages = [
            '1-0-0',
            '0-1-0',
            '0-0-1',
            '1-1-0',
            '1-0-1',
            '0-1-1',
            '1-1-1',
            '2-0-0',
            '0-2-0',
            '0-0-2',
            '2-2-2',
        ];

        foreach ($dosages as $dosage) {
            MasterDosage::query()->firstOrCreate(['dosage' => $dosage]);
        }

        $this->command?->info('Global master dosages seeded successfully!');
    }
}
