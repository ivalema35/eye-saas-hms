<?php

namespace Database\Seeders;

use App\Models\Hospital\ChiefComplaint;
use App\Models\Platform\Tenant;
use Illuminate\Database\Seeder;

class ChiefComplaintSeeder extends Seeder
{
    public function run(): void
    {
        $complaints = [
            'Black spot',
            'Cataract check up',
            'Diplopia',
            'Discharge',
            'DmVn ( Distance + Near)',
            'DmVn ( Distance)',
            'DmVn ( Near)',
            'Eyestrain',
            'Floater',
            'For Lasik',
        ];

        $tenants = Tenant::query()->get();

        ChiefComplaint::unguarded(function () use ($tenants, $complaints): void {
            foreach ($tenants as $tenant) {
                foreach ($complaints as $complaint) {
                    ChiefComplaint::query()->updateOrCreate(
                        [
                            'tenant_id' => $tenant->id,
                            'value' => $complaint,
                        ]
                    );
                }
            }
        });

        $this->command?->info('Chief Complaints seeded successfully for all tenants!');
    }
}
