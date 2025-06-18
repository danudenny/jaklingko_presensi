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
use App\Services\ScheduleSummaryMaterializedService;
use Illuminate\Support\Facades\Log;

class ScheduleSummaryMaterializedExport implements FromArray, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    protected $startDate;
    protected $endDate;
    protected $routeId;
    protected $unitIds;
    protected $driverType;
    protected $materializedService;

    public function __construct($startDate = null, $endDate = null, $routeId = null, $unitIds = null, $driverType = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->routeId = $routeId;
        $this->unitIds = $unitIds;
        $this->driverType = $driverType;
        $this->materializedService = new ScheduleSummaryMaterializedService();
    }

    public function array(): array
    {
        $startTime = microtime(true);
        $memoryStart = memory_get_usage(true);
        
        Log::info('Starting materialized export', [
            'memory_start' => $memoryStart,
            'filters' => [
                'start_date' => $this->startDate,
                'end_date' => $this->endDate,
                'route_id' => $this->routeId,
                'unit_ids' => $this->unitIds,
                'driver_type' => $this->driverType
            ]
        ]);

        // Check if materialized data is fresh
        if (!$this->materializedService->isMaterializedDataFresh()) {
            Log::warning('Materialized data is stale, consider refreshing');
        }

        // Get data from materialized table (super fast!)
        $results = $this->materializedService->getSummaryData(
            $this->startDate,
            $this->endDate,
            $this->routeId,
            $this->unitIds,
            $this->driverType
        );

        // Convert to array format
        $data = [];
        foreach ($results as $row) {
            $data[] = [
                $row->driver_id,
                $row->driver_name,
                $row->unit_number,
                $row->route_name,
                $row->driver_rekening ?? '',
                (int)$row->total_days
            ];
        }

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        $memoryUsed = memory_get_usage(true) - $memoryStart;

        Log::info('Materialized export completed', [
            'records_exported' => count($data),
            'execution_time_ms' => round($executionTime, 2),
            'memory_used_mb' => round($memoryUsed / 1024 / 1024, 2),
            'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2)
        ]);

        return $data;
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
        return 'Schedule Summary (Materialized)';
    }

    public function styles(Worksheet $sheet)
    {
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
            'A:F' => [
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
            'A:A' => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ],
            'F:F' => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ],
        ];
    }
}
