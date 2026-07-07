<?php

namespace Database\Seeders;

use App\Models\Platform\MasterMedicineRoute;
use Illuminate\Database\Seeder;

class MasterMedicineRouteSeeder extends Seeder
{
    public function run(): void
    {
        $routes = [
            'Oral',
            'Left Eye (OS)',
            'Right Eye (OD)',
            'Both Eyes (OU)',
            'Topical',
            'Intravenous (IV)',
            'Intramuscular (IM)',
            'Intravitreal',
            'Subconjunctival',
            'Intracameral',
            'Peribulbar',
            'Retrobulbar',
            'Others',
        ];

        foreach ($routes as $route) {
            MasterMedicineRoute::query()->firstOrCreate(['name' => $route]);
        }

        $this->command?->info('Global master medicine routes seeded successfully!');
    }
}
