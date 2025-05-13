<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UnitNonPoolTemplateExport implements FromArray, WithHeadings, WithStyles
{
    /**
     * @return array
     */
    public function array(): array
    {
        // Sample data for the template
        return [
            [
                'unit_number' => '101',
                'plate_number' => 'B 9876 XYZ',
                'status' => 'aktif',
            ],
            [
                'unit_number' => '102',
                'plate_number' => 'B 5432 UVW',
                'status' => 'aktif',
            ],
        ];
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'unit_number',
            'plate_number',
            'status',
        ];
    }

    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row (header row)
            1 => ['font' => ['bold' => true]],
        ];
    }
}
