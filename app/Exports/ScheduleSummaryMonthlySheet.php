<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ScheduleSummaryMonthlySheet implements FromArray, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    protected $monthName;
    protected $data;

    public function __construct($monthName, $data)
    {
        $this->monthName = $monthName;
        $this->data = $data;
    }

    public function array(): array
    {
        $exportData = [];
        foreach ($this->data as $row) {
            $exportData[] = [
                $row['driver_id'],
                $row['driver_name'],
                $row['unit_number'],
                $row['route_name'],
                $row['driver_rekening'],
                $row['total_days']
            ];
        }
        return $exportData;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nama Driver',
            'Unit',
            'Rute',
            'No Rekening',
            'Total Days'
        ];
    }

    public function title(): string
    {
        return $this->monthName;
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = count($this->data) + 1; // +1 for header row
        
        return [
            // Style the header row
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ],
            // Style all data rows
            "A1:F{$lastRow}" => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC'],
                    ],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
            // Center align ID and Total Days columns
            "A1:A{$lastRow}" => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ],
            "F1:F{$lastRow}" => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ],
        ];
    }
}
