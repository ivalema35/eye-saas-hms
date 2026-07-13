<?php

namespace App\Imports;

use App\Models\Platform\MasterDosage;
use App\Models\Platform\MasterMedicine;
use App\Models\Platform\MasterMedicineType;
use App\Services\Platform\MedicineTenantSync;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class MedicineMasterImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    public int $imported = 0;
    public int $skipped = 0;

    /** @var array<int, string> */
    public array $errors = [];

    public function collection(Collection $rows): void
    {
        foreach ($rows as $i => $row) {
            $rowNumber = $i + 2; // +1 for zero-index, +1 for the heading row

            $name = trim((string) ($row['medicine_name'] ?? ''));
            if ($name === '') {
                $this->skipped++;
                $this->errors[] = "Row {$rowNumber}: Medicine Name is required.";
                continue;
            }

            if (MasterMedicine::whereRaw('LOWER(name) = ?', [strtolower($name)])->exists()) {
                $this->skipped++;
                $this->errors[] = "Row {$rowNumber}: Medicine \"{$name}\" already exists — skipped.";
                continue;
            }

            $typeName = trim((string) ($row['medicine_type'] ?? ''));
            $type = $typeName !== ''
                ? MasterMedicineType::whereRaw('LOWER(name) = ?', [strtolower($typeName)])->first()
                : null;

            if (! $type) {
                $this->skipped++;
                $this->errors[] = $typeName === ''
                    ? "Row {$rowNumber}: Medicine Type is required."
                    : "Row {$rowNumber}: Medicine Type \"{$typeName}\" not found.";
                continue;
            }

            $dosageValue = trim((string) ($row['dosage'] ?? ''));
            $dosage = $dosageValue !== ''
                ? MasterDosage::whereRaw('LOWER(dosage) = ?', [strtolower($dosageValue)])->first()
                : null;

            if (! $dosage) {
                $this->skipped++;
                $this->errors[] = $dosageValue === ''
                    ? "Row {$rowNumber}: Dosage is required."
                    : "Row {$rowNumber}: Dosage \"{$dosageValue}\" not found.";
                continue;
            }

            $price = $row['price'] ?? null;

            $medicine = MasterMedicine::create([
                'master_medicine_type_id' => $type->id,
                'master_dosage_id' => $dosage->id,
                'name' => $name,
                'company' => trim((string) ($row['company'] ?? '')) ?: null,
                'composition' => trim((string) ($row['composition'] ?? '')) ?: null,
                'price' => is_numeric($price) ? (float) $price : null,
                'is_active' => true,
            ]);

            MedicineTenantSync::pushCreate($medicine);

            $this->imported++;
        }
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
