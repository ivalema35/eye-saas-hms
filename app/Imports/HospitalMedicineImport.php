<?php

namespace App\Imports;

use App\Models\Hospital\Dosage;
use App\Models\Hospital\Medicine;
use App\Models\Hospital\MedicineType;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Hospital-level Medicine Excel import (tenant-scoped).
 *
 * Mirrors MedicineMasterImport (Super Admin's global catalog import) but
 * resolves Medicine Type / Dosage against this hospital's own tenant-scoped
 * tables and creates directly into the tenant's `medicines` table.
 */
class HospitalMedicineImport implements ToCollection, WithHeadingRow, WithChunkReading
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

            if (Medicine::whereRaw('LOWER(name) = ?', [strtolower($name)])->exists()) {
                $this->skipped++;
                $this->errors[] = "Row {$rowNumber}: Medicine \"{$name}\" already exists — skipped.";
                continue;
            }

            $typeName = trim((string) ($row['medicine_type'] ?? ''));
            $type = $typeName !== ''
                ? MedicineType::whereRaw('LOWER(name) = ?', [strtolower($typeName)])->first()
                : null;

            if (!$type) {
                $this->skipped++;
                $this->errors[] = $typeName === ''
                    ? "Row {$rowNumber}: Medicine Type is required."
                    : "Row {$rowNumber}: Medicine Type \"{$typeName}\" not found.";
                continue;
            }

            $dosageValue = trim((string) ($row['dosage'] ?? ''));
            $dosage = $dosageValue !== ''
                ? Dosage::whereRaw('LOWER(dosage) = ?', [strtolower($dosageValue)])->first()
                : null;

            if (!$dosage) {
                $this->skipped++;
                $this->errors[] = $dosageValue === ''
                    ? "Row {$rowNumber}: Dosage is required."
                    : "Row {$rowNumber}: Dosage \"{$dosageValue}\" not found.";
                continue;
            }

            $duration = trim((string) ($row['duration'] ?? ''));
            $qty = trim((string) ($row['qty'] ?? ''));

            if ($duration === '' || $qty === '') {
                $this->skipped++;
                $this->errors[] = "Row {$rowNumber}: Duration and Qty are required.";
                continue;
            }

            $price = $row['price'] ?? null;

            Medicine::create([
                'medicine_type_id' => $type->id,
                'dosage_id' => $dosage->id,
                'name' => $name,
                'duration' => $duration,
                'qty' => $qty,
                'company' => trim((string) ($row['company'] ?? '')) ?: null,
                'composition' => trim((string) ($row['composition'] ?? '')) ?: null,
                'price' => is_numeric($price) ? (float) $price : null,
            ]);

            $this->imported++;
        }
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
