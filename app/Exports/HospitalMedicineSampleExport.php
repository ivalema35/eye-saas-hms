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
            ['Vitamin C Tablet', '', '', '', '', '', '', ''],
        ];
    }

    public function headings(): array
    {
        return ['Medicine Name', 'Medicine Type', 'Dosage', 'Duration', 'Qty', 'Company', 'Composition', 'Price'];
    }
}
