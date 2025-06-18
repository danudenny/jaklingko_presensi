<?php

namespace App\Exports;

use App\Models\Schedule;
use App\Models\Route;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ScheduleSummaryExport implements FromQuery, WithHeadings, WithMapping, WithTitle, WithStyles, ShouldAutoSize, WithChunkReading
{
    protected $startDate;
    protected $endDate;
    protected $routeId;
    protected $unitIds;
    protected $driverType;

    public function __construct($startDate = null, $endDate = null, $routeId = null, $unitIds = null, $driverType = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->routeId = $routeId;
        $this->unitIds = $unitIds;
        $this->driverType = $driverType;
    }

    public function query()
    {
        // Build optimized query with proper indexing hints
        $query = DB::table('schedules as s')
            ->select([
                'd.id as driver_id',
                'd.name as driver_name',
                'u.unit_number',
                'r.name as route_name',
                'd.rekening',
                DB::raw('COUNT(*) as total_days')
            ])
            ->join('drivers as d', 's.driver_id', '=', 'd.id')
            ->join('units as u', 's.unit_id', '=', 'u.id')
            ->join('routes as r', 's.route_id', '=', 'r.id')
            ->where('s.status', 'scheduled')
            ->groupBy('d.id', 'd.name', 'u.unit_number', 'r.name', 'd.rekening')
            ->orderBy('d.name')
            ->orderBy('u.unit_number');

        // Apply date filters
        if ($this->startDate && $this->endDate) {
            $query->whereBetween('s.schedule_date', [$this->startDate, $this->endDate]);
        } elseif ($this->startDate) {
            $query->where('s.schedule_date', '>=', $this->startDate);
        } elseif ($this->endDate) {
            $query->where('s.schedule_date', '<=', $this->endDate);
        }

        // Apply route filter
        if ($this->routeId && $this->routeId !== 'all') {
            $query->where('s.route_id', $this->routeId);
        }

        // Apply unit filter
        if ($this->unitIds && $this->unitIds !== 'all') {
            $unitIdsArray = is_array($this->unitIds) ? $this->unitIds : explode(',', $this->unitIds);
            $query->whereIn('s.unit_id', $unitIdsArray);
        }

        // Apply driver type filter
        if ($this->driverType) {
            $query->where('d.type', $this->driverType);
        }

        return $query;
    }

    public function chunkSize(): int
    {
        return 100; // Process 100 rows at a time
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

    public function map($row): array
    {
        return [
            $row->driver_id ?? '',
            $row->driver_name ?? '',
            $row->unit_number ?? '',
            $row->route_name ?? '',
            $row->rekening ?? '',
            $row->total_days ?? 0
        ];
    }

    public function title(): string
    {
        return 'Schedule Summary';
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