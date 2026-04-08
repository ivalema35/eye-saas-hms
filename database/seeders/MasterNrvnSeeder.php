<?php

namespace Database\Seeders;

use App\Models\Hospital\MasterNrvn;
use App\Models\Platform\Tenant;
use Illuminate\Database\Seeder;

class MasterNrvnSeeder extends Seeder
{
    public function run(): void
    {
        $nrvnValues = [
            'NP',
            'N6',
            'N8',
            'N10',
            'N12',
            'N18',
            'N24',
            'N36',
            'N6 1FT',
            'N8 1FT',
            'N10 1FT',
            'N12 1FT',
            'N18 1FT',
            'N24 1FT',
        ];

        $tenants = Tenant::query()->get();

        MasterNrvn::unguarded(function () use ($tenants, $nrvnValues): void {
            foreach ($tenants as $tenant) {
                foreach ($nrvnValues as $value) {
                    MasterNrvn::query()->updateOrCreate(
                        [
                            'tenant_id' => $tenant->id,
                            'value' => $value,
                        ]
                    );
                }
            }
        });

        $this->command?->info('Nr/Vn (Near Vision) values seeded successfully for all tenants!');
    }
}
