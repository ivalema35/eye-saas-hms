<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PatientReportExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(private Collection $patients) {}

    /**
     * @return Collection<int, mixed>
     */
    public function collection(): Collection
    {
        return $this->patients;
    }

    public function headings(): array
    {
        return [
            'MRD No',
            'Date',
            'Patient Name',
            'Contact',
            'City',
            'Age',
            'Type',
            'Doctor',
            'Receptionist',
            'Case Type',
            'Fee (₹)',
        ];
    }

    public function map($patient): array
    {
        $caseTypeValue = strtolower(trim((string) ($patient->caseType?->case_type ?? '')));
        $caseTypeLabel = match (true) {
            str_contains($caseTypeValue, 'general') => 'General',
            str_contains($caseTypeValue, 'old') => 'Old',
            str_contains($caseTypeValue, 'new') => 'New',
            default => $patient->caseType?->case_type ?: '-',
        };

        return [
            $patient->patient_code ?: 'N/A',
            $patient->created_at?->format('d-m-Y h:i A'),
            $patient->full_name,
            $patient->contact_no,
            $patient->location?->city ?? $patient->masterCity?->name ?: '-',
            $patient->age ?: '-',
            in_array((string) $patient->type, ['walkin', '0'], true) ? 'Walk-in' : 'Phone',
            $patient->doctor?->name ?: '-',
            $patient->reception?->name ?: '-',
            $caseTypeLabel,
            $patient->case_fee,
        ];
    }
}
