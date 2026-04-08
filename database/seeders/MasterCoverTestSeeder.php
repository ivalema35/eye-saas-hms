<?php

namespace Database\Seeders;

use App\Models\Hospital\MasterCoverTest;
use App\Models\Platform\Tenant;
use Illuminate\Database\Seeder;

class MasterCoverTestSeeder extends Seeder
{
    public function run(): void
    {
        $coverTestValues = [
            'Orthophoria',
            'Esophoria',
            'Exophoria',
            'Hyperphoria',
            'Hypophoria',
            'Esotropia',
            'Exotropia',
            'Hypertropia',
            'Hypotropia',
            'Alternating Esotropia',
            'Alternating Exotropia',
        ];

        $tenants = Tenant::query()->get();

        MasterCoverTest::unguarded(function () use ($tenants, $coverTestValues): void {
            foreach ($tenants as $tenant) {
                foreach ($coverTestValues as $value) {
                    MasterCoverTest::query()->updateOrCreate([
                        'tenant_id' => $tenant->id,
                        'value' => $value,
                    ]);
                }
            }
        });

        $this->command?->info('Cover Test clinical values seeded successfully for all tenants!');
    }
}
