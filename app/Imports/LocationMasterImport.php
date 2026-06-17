<?php

namespace App\Imports;

use App\Models\Platform\MasterCity;
use App\Models\Platform\MasterCountry;
use App\Models\Platform\MasterDistrict;
use App\Models\Platform\MasterState;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Support\Collection;

class LocationMasterImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    public int $imported = 0;
    public int $skipped  = 0;

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $countryName  = MasterCountry::normalize($row['country']  ?? '');
            $stateName    = MasterState::normalize($row['state']      ?? '');
            $districtName = MasterDistrict::normalize($row['district'] ?? '');
            $cityName     = MasterCity::normalize($row['city']        ?? '');

            if ($countryName === '' || $stateName === '' || $cityName === '') {
                $this->skipped++;
                continue;
            }

            // Find or create Country
            $country = MasterCountry::firstOrCreate(
                ['name' => $countryName],
                ['is_active' => true]
            );

            // Find or create State
            $state = MasterState::firstOrCreate(
                ['country_id' => $country->id, 'name' => $stateName],
                ['is_active' => true]
            );

            // Find or create District (optional)
            $district = null;
            if ($districtName !== '') {
                $district = MasterDistrict::firstOrCreate(
                    ['state_id' => $state->id, 'name' => $districtName],
                    ['is_active' => true]
                );
            }

            // Find or create City
            $cityExists = MasterCity::where('state_id', $state->id)
                ->where('district_id', $district?->id)
                ->where('name', $cityName)
                ->exists();

            if ($cityExists) {
                $this->skipped++;
                continue;
            }

            MasterCity::create([
                'state_id'    => $state->id,
                'district_id' => $district?->id,
                'name'        => $cityName,
                'is_active'   => true,
            ]);

            $this->imported++;
        }
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
