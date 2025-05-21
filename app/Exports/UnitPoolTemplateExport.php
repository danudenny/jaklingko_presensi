<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UnitPoolTemplateExport implements FromArray, WithHeadings, WithStyles
{
    /**
     * @return array
     */
    public function array(): array
    {
        // Sample data for the template
        return [
            [
                'unit_number' => '001',
                'plate_number' => 'B 1234 ABC',
                'unit_reg' => 'REG001',
                'serial_number' => 'SN12345',
                'kir' => 'KIR123',
                'expired_stnk' => '2025-12-31',
                'expired_kir' => '2025-12-31',
                'expired_kp' => '2025-12-31',
                'status' => 'aktif',
                'route_codes' => 'JAK01,JAK02',
            ],
            [
                'unit_number' => '002',
                'plate_number' => 'B 5678 DEF',
                'unit_reg' => 'REG002',
                'serial_number' => 'SN67890',
                'kir' => 'KIR456',
                'expired_stnk' => '2025-10-15',
                'expired_kir' => '2025-11-20',
                'expired_kp' => '2025-09-30',
                'status' => 'aktif',
                'route_codes' => 'JAK01,JAK03',
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
            'unit_reg',
            'serial_number',
            'kir',
            'expired_stnk',
            'expired_kir',
            'expired_kp',
            'status',
            'route_codes',
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
