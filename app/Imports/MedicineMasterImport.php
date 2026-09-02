<?php

namespace App\Imports;

use App\Models\Platform\MasterDosage;
use App\Models\Platform\MasterMedicine;
use App\Models\Platform\MasterMedicineType;
use App\Services\Platform\MedicineTenantSync;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class MedicineMasterImport implements ToCollection, WithChunkReading, WithHeadingRow
{
    public int $imported = 0;

    public int $skipped = 0;

    /** @var array<int, string> */
    public array $errors = [];

    /** @param  list<int>  $tenantIds */
    public function __construct(
        private readonly array $tenantIds = [],
    ) {}

    public function collection(Collection $rows): void
    {
        foreach ($rows as $i => $row) {
            $rowNumber = $i + 2;

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
            $type = null;
            if ($typeName !== '') {
                $type = MasterMedicineType::whereRaw('LOWER(name) = ?', [strtolower($typeName)])->first();
                if (! $type) {
                    $this->skipped++;
                    $this->errors[] = "Row {$rowNumber}: Medicine Type \"{$typeName}\" not found.";
                    continue;
                }
            }

            $dosageValue = trim((string) ($row['dosage'] ?? ''));
            $dosage = null;
            if ($dosageValue !== '') {
                $dosage = MasterDosage::whereRaw('LOWER(dosage) = ?', [strtolower($dosageValue)])->first();
                if (! $dosage) {
                    $this->skipped++;
                    $this->errors[] = "Row {$rowNumber}: Dosage \"{$dosageValue}\" not found.";
                    continue;
                }
            }

            $duration = trim((string) ($row['duration'] ?? ''));
            $qtyRaw = trim((string) ($row['qty'] ?? ''));
            $qty = null;

            if ($qtyRaw !== '') {
                if (! preg_match('/^\d+$/', $qtyRaw)) {
                    $this->skipped++;
                    $this->errors[] = "Row {$rowNumber}: Qty must be a whole number (e.g. 10, 1).";
                    continue;
                }

                $qty = (string) (int) $qtyRaw;
            }

            $price = $row['price'] ?? null;

            try {
                DB::transaction(function () use ($type, $dosage, $name, $duration, $qty, $price, $row) {
                    $medicine = MasterMedicine::create([
                        'master_medicine_type_id' => $type?->id,
                        'master_dosage_id' => $dosage?->id,
                        'name' => $name,
                        'duration' => $duration !== '' ? $duration : null,
                        'qty' => $qty,
                        'company' => trim((string) ($row['company'] ?? '')) ?: null,
                        'composition' => trim((string) ($row['composition'] ?? '')) ?: null,
                        'price' => is_numeric($price) ? (float) $price : null,
                        'is_active' => true,
                    ]);

                    MedicineTenantSync::pushCreateForTenants($medicine, $this->tenantIds);
                });
            } catch (\Throwable $e) {
                $this->skipped++;
                $this->errors[] = "Row {$rowNumber}: {$name} — sync failed ({$e->getMessage()}).";
                continue;
            }

            $this->imported++;
        }
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
