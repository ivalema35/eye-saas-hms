<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class LocationMasterSampleExport implements FromArray, WithHeadings
{
    public function array(): array
    {
        return [
            ['India', 'Gujarat', 'Ahmedabad', 'Ahmedabad'],
            ['India', 'Maharashtra', 'Pune', 'Pune'],
        ];
    }

    public function headings(): array
    {
        return ['Country', 'State', 'District', 'City'];
    }
}
