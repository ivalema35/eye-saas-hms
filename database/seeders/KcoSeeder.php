<?php

namespace Database\Seeders;

use App\Models\Hospital\Kco;
use App\Models\Platform\Tenant;
use Illuminate\Database\Seeder;

class KcoSeeder extends Seeder
{
    public function run(): void
    {
        $kcos = [
            'Acidity Problem',
            'Arthritis',
            'Asthma',
            'Blood Thinner',
            'Brain Problem',
            'Cancer',
            'Cholesterol',
            'D.M',
            'H.T',
            'Heart Problem',
        ];

        $tenants = Tenant::query()->get();

        Kco::unguarded(function () use ($tenants, $kcos): void {
            foreach ($tenants as $tenant) {
                foreach ($kcos as $kco) {
                    Kco::query()->updateOrCreate(
                        [
                            'tenant_id' => $tenant->id,
                            'value' => $kco,
                        ]
                    );
                }
            }
        });

        $this->command?->info('KCO (Known Case Of) values seeded successfully for all tenants!');
    }
}
