<?php

namespace Database\Seeders;

use App\Models\Hospital\Dosage;
use App\Models\Platform\Tenant;
use Illuminate\Database\Seeder;

class DosageSeeder extends Seeder
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

        $tenants = Tenant::query()->get();

        Dosage::unguarded(function () use ($tenants, $dosages): void {
            foreach ($tenants as $tenant) {
                foreach ($dosages as $dosage) {
                    Dosage::query()->updateOrCreate([
                        'tenant_id' => $tenant->id,
                        'dosage' => $dosage,
                    ]);
                }
            }
        });

        $this->command?->info('Dosages seeded successfully for all tenants!');
    }
}
