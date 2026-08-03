<?php

/**
 * GenericArrayExport.php
 *
 * PURPOSE: OT Workflow Upgrade — Phase 8 (Reports Module). Single reusable Excel
 * export for all OT report types — rows/headings are pre-built by
 * OtReportController so every report shares one export class instead of one
 * class per report. See docs/OT_WORKFLOW_UPGRADE_PRD.md §8.
 */

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class GenericArrayExport implements FromArray, WithHeadings
{
    public function __construct(private array $rows, private array $headings)
    {
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return $this->headings;
    }
}
