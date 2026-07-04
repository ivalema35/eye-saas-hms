<?php

namespace Database\Seeders;

use App\Models\Hospital\MasterHno;
use App\Models\Platform\Tenant;
use Illuminate\Database\Seeder;

class MasterHnoSeeder extends Seeder
{
    public function run(): void
    {
        $values = [
            'Diabetes Mellitus (DM)',
            'Hypertension (HTN)',
            'Asthma',
            'COPD',
            'Thyroid Disorder',
            'Cardiac Disease',
            'Kidney Disease (CKD)',
            'Liver Disease',
            'Stroke (CVA)',
            'Epilepsy',
            'Tuberculosis (TB)',
            'Hepatitis',
            'HIV',
            'Bleeding Disorder',
            'Anemia',
        ];

        $tenants = Tenant::query()->get();

        MasterHno::unguarded(function () use ($tenants, $values): void {
            foreach ($tenants as $tenant) {
                foreach ($values as $value) {
                    MasterHno::query()->updateOrCreate([
                        'tenant_id' => $tenant->id,
                        'value' => $value,
                    ]);
                }
            }
        });

        $this->command?->info('H/O (History Of) values seeded successfully for all tenants!');
    }
}
