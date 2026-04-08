<?php

namespace Database\Seeders;

use App\Models\Hospital\MasterAdvice;
use App\Models\Platform\Tenant;
use Illuminate\Database\Seeder;

class MasterAdviceSeeder extends Seeder
{
    public function run(): void
    {
        $adviceValues = [
            'Avoid dust and smoke',
            'Cold compresses',
            'Dark goggles',
            'Don\'t rub eyes',
            'Eye patching',
            'Frequent blinking',
            'Lid hygiene',
            'No head bath',
            'No heavy lifting',
            'No swimming',
            'Protective glasses',
            'Read in good light',
            'Restrict mobile/TV use',
            'Use clean handkerchief',
        ];

        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            MasterAdvice::unguarded(function () use ($tenant, $adviceValues): void {
                foreach ($adviceValues as $value) {
                    MasterAdvice::updateOrCreate(
                        [
                            'tenant_id' => $tenant->id,
                            'value' => $value,
                        ]
                    );
                }
            });
        }

        $this->command?->info('Advice master values seeded successfully!');
    }
}
