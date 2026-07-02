<?php

namespace Database\Seeders;

use App\Models\Hospital\MedicineType;
use App\Models\Platform\Tenant;
use Illuminate\Database\Seeder;

class MedicineTypeSeeder extends Seeder
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

        $tenants = Tenant::query()->get();

        MedicineType::unguarded(function () use ($tenants, $types): void {
            foreach ($tenants as $tenant) {
                foreach ($types as $type) {
                    $existing = MedicineType::withTrashed()
                        ->where('tenant_id', $tenant->id)
                        ->where('name', $type)
                        ->first();

                    if ($existing) {
                        if ($existing->trashed()) {
                            $existing->restore();
                        }

                        continue;
                    }

                    MedicineType::create([
                        'tenant_id' => $tenant->id,
                        'name' => $type,
                    ]);
                }
            }
        });

        $this->command?->info('Medicine types seeded successfully for all tenants!');
    }
}
