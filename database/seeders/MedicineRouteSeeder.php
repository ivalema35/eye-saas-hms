<?php

namespace Database\Seeders;

use App\Models\Hospital\MedicineRoute;
use App\Models\Platform\Tenant;
use Illuminate\Database\Seeder;

class MedicineRouteSeeder extends Seeder
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

        $tenants = Tenant::query()->get();

        MedicineRoute::unguarded(function () use ($tenants, $routes): void {
            foreach ($tenants as $tenant) {
                foreach ($routes as $route) {
                    $existing = MedicineRoute::withTrashed()
                        ->where('tenant_id', $tenant->id)
                        ->where('name', $route)
                        ->first();

                    if ($existing) {
                        if ($existing->trashed()) {
                            $existing->restore();
                        }

                        continue;
                    }

                    MedicineRoute::create([
                        'tenant_id' => $tenant->id,
                        'name' => $route,
                    ]);
                }
            }
        });

        $this->command?->info('Medicine routes seeded successfully for all tenants!');
    }
}
