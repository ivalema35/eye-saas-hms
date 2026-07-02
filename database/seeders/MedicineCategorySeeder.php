<?php

namespace Database\Seeders;

use App\Models\Hospital\MedicineCategory;
use App\Models\Platform\Tenant;
use Illuminate\Database\Seeder;

class MedicineCategorySeeder extends Seeder
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

        $tenants = Tenant::query()->get();

        MedicineCategory::unguarded(function () use ($tenants, $categories): void {
            foreach ($tenants as $tenant) {
                foreach ($categories as $category) {
                    $existing = MedicineCategory::withTrashed()
                        ->where('tenant_id', $tenant->id)
                        ->where('name', $category)
                        ->first();

                    if ($existing) {
                        if ($existing->trashed()) {
                            $existing->restore();
                        }

                        continue;
                    }

                    MedicineCategory::create([
                        'tenant_id' => $tenant->id,
                        'name' => $category,
                    ]);
                }
            }
        });

        $this->command?->info('Medicine categories seeded successfully for all tenants!');
    }
}
