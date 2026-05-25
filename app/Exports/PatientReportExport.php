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
            'Doctor',
            'Receptionist',
            'Case Type',
            'Fee (₹)',
            'Type',
        ];
    }

    public function map($patient): array
    {
        return [
            $patient->patient_code ?: 'N/A',
            $patient->created_at?->format('d-m-Y h:i A'),
            $patient->full_name,
            $patient->contact_no,
            $patient->location?->name ?: '-',
            $patient->doctor?->name ?: '-',
            $patient->reception?->name ?: '-',
            $patient->caseType?->case_type ?: '-',
            $patient->case_fee,
            in_array((string) $patient->type, ['walkin', '0'], true) ? 'Walk-in' : 'Phone',
        ];
    }
}
