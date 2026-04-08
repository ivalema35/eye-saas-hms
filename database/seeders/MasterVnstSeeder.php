<?php

namespace Database\Seeders;

use App\Models\Hospital\MasterVnst;
use App\Models\Platform\Tenant;
use Illuminate\Database\Seeder;

class MasterVnstSeeder extends Seeder
{
    public function run(): void
    {
        $visionValues = [
            '6/6',
            '6/6 P',
            '6/9',
            '6/9 P',
            '6/12',
            '6/12 P',
            '6/18',
            '6/18 P',
            '6/24',
            '6/24 P',
            '6/36',
            '6/36 P',
            '6/60',
            'CF3M',
            'CF2M',
            'CF1M',
            'CFCF',
            'HM',
            'HM/PL/PR4+',
            'HM/PL/PR inacc.',
            'NOPL',
            '6/4',
            '6/4 P',
            '6/5',
            '6/5 P',
        ];

        $tenants = Tenant::query()->get();

        MasterVnst::unguarded(function () use ($tenants, $visionValues): void {
            foreach ($tenants as $tenant) {
                foreach ($visionValues as $value) {
                    MasterVnst::query()->updateOrCreate([
                        'tenant_id' => $tenant->id,
                        'value' => $value,
                    ]);
                }
            }
        });

        $this->command?->info('Vn C ST (Vision with Sub-Trial) values seeded successfully for all tenants!');
    }
}
