<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class HospitalMedicineSampleExport implements FromArray, WithHeadings
{
    public function array(): array
    {
        return [
            ['Moxifloxacin Eye Drop', 'Eye Drop', '1-0-0', '4 days', '10', 'Sun Pharma', 'Moxifloxacin 0.5% w/v', 85.00],
            ['Paracetamol 500mg', 'Tablet', '1-1-1', '5 days', '15', 'Cipla', 'Paracetamol 500mg', 20.00],
        ];
    }

    public function headings(): array
    {
        return ['Medicine Name', 'Medicine Type', 'Dosage', 'Duration', 'Qty', 'Company', 'Composition', 'Price'];
    }
}
