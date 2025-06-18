<?php

namespace App\Exports;

use App\Models\MaintenanceLog;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Carbon\Carbon;

class MaintenanceLogsExport implements WithMultipleSheets
{
    protected $startDate;
    protected $endDate;
    protected $unitIds;
    protected $routeId;

    public function __construct($startDate = null, $endDate = null, $status = null, $unitIds = null, $routeId = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        // Note: $status parameter is kept for backward compatibility but not used
        $this->unitIds = $unitIds;
        $this->routeId = $routeId;
    }

    public function sheets(): array
    {
        return [
            'Maintenance Logs Summary' => new MaintenanceLogsSummarySheet($this->startDate, $this->endDate, $this->unitIds, $this->routeId),
            'Detailed Breakdown' => new MaintenanceLogsDetailedSheet($this->startDate, $this->endDate, $this->unitIds, $this->routeId),
        ];
    }
}

class MaintenanceLogsSummarySheet implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $startDate;
    protected $endDate;
    protected $unitIds;
    protected $routeId;

    public function __construct($startDate, $endDate, $unitIds, $routeId)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->unitIds = $unitIds;
        $this->routeId = $routeId;
    }

    public function collection()
    {
        $query = MaintenanceLog::with(['unit', 'route', 'driver', 'photos', 'scheduleHistory']);

        // Apply filters
        if ($this->startDate) {
            $query->whereDate('date_reported', '>=', $this->startDate);
        }

        if ($this->endDate) {
            $query->whereDate('date_reported', '<=', $this->endDate);
        }

        if ($this->unitIds && $this->unitIds !== 'all') {
            $unitIdArray = is_string($this->unitIds) ? explode(',', $this->unitIds) : $this->unitIds;
            $query->whereIn('unit_id', $unitIdArray);
        }

        if ($this->routeId && $this->routeId !== 'all') {
            $query->where('route_id', $this->routeId);
        }

        return $query->orderBy('date_reported', 'desc')
                    ->orderBy('time_reported', 'desc')
                    ->get();
    }

    public function headings(): array
    {
        return [
            'No',
            'Tanggal Laporan',
            'Waktu Laporan',
            'Unit',
            'Rute',
            'Pengemudi',
            'Deskripsi',
            'Tipe',
            'Suku Cadang',
            'Kategori',
            'Sumber Suku Cadang',
            'Status',
            'Dalam Jadwal',
            'Total Biaya',
            'Jumlah Foto',
            'Tanggal Dibuat',
            'Tanggal Diperbarui'
        ];
    }

    public function map($maintenanceLog): array
    {
        static $rowNumber = 0;
        $rowNumber++;

        // Calculate total cost
        $totalCost = 0;
        if ($maintenanceLog->costs && is_array($maintenanceLog->costs)) {
            foreach ($maintenanceLog->costs as $cost) {
                $totalCost += isset($cost['amount']) ? (float) $cost['amount'] : 0;
            }
        }

        return [
            $rowNumber,
            $maintenanceLog->date_reported ? $maintenanceLog->date_reported->format('d/m/Y') : '',
            $maintenanceLog->time_reported ? $maintenanceLog->time_reported->format('H:i') : '',
            $maintenanceLog->unit ? $maintenanceLog->unit->unit_number : '',
            $maintenanceLog->route ? $maintenanceLog->route->name : '',
            $maintenanceLog->driver ? $maintenanceLog->driver->name : '',
            $maintenanceLog->description,
            ucfirst($maintenanceLog->type),
            $maintenanceLog->parts,
            $maintenanceLog->category ? ucfirst($maintenanceLog->category) : '',
            $maintenanceLog->source_of_sparepart,
            ucfirst(str_replace('_', ' ', $maintenanceLog->status)),
            $maintenanceLog->on_schedule ? 'Ya' : 'Tidak',
            'Rp ' . number_format($totalCost, 0, ',', '.'),
            $maintenanceLog->photos ? $maintenanceLog->photos->count() : 0,
            $maintenanceLog->created_at ? $maintenanceLog->created_at->format('d/m/Y H:i') : '',
            $maintenanceLog->updated_at ? $maintenanceLog->updated_at->format('d/m/Y H:i') : ''
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as header
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4'],
                ],
            ],
            // Add borders to all cells
            'A1:Q1000' => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ],
        ];
    }
}

class MaintenanceLogsDetailedSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $startDate;
    protected $endDate;
    protected $unitIds;
    protected $routeId;

    public function __construct($startDate, $endDate, $unitIds, $routeId)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->unitIds = $unitIds;
        $this->routeId = $routeId;
    }

    public function collection()
    {
        $query = MaintenanceLog::with(['unit', 'route', 'driver', 'photos', 'scheduleHistory']);

        // Apply filters
        if ($this->startDate) {
            $query->whereDate('date_reported', '>=', $this->startDate);
        }

        if ($this->endDate) {
            $query->whereDate('date_reported', '<=', $this->endDate);
        }

        if ($this->unitIds && $this->unitIds !== 'all') {
            $unitIdArray = is_string($this->unitIds) ? explode(',', $this->unitIds) : $this->unitIds;
            $query->whereIn('unit_id', $unitIdArray);
        }

        if ($this->routeId && $this->routeId !== 'all') {
            $query->where('route_id', $this->routeId);
        }

        $maintenanceLogs = $query->orderBy('date_reported', 'desc')
                                ->orderBy('time_reported', 'desc')
                                ->get();

        // Create detailed breakdown with cost details and photos
        $detailedData = collect();
        
        foreach ($maintenanceLogs as $log) {
            // Add main log info
            $baseData = [
                'log_id' => $log->id,
                'date_reported' => $log->date_reported,
                'time_reported' => $log->time_reported,
                'unit' => $log->unit ? $log->unit->unit_number : '',
                'route' => $log->route ? $log->route->name : '',
                'driver' => $log->driver ? $log->driver->name : '',
                'description' => $log->description,
                'type' => $log->type,
                'parts' => $log->parts,
                'category' => $log->category,
                'source_of_sparepart' => $log->source_of_sparepart,
                'status' => $log->status,
                'on_schedule' => $log->on_schedule,
                'created_at' => $log->created_at,
                'updated_at' => $log->updated_at,
            ];

            // Add cost details
            if ($log->costs && is_array($log->costs) && count($log->costs) > 0) {
                foreach ($log->costs as $index => $cost) {
                    $costData = array_merge($baseData, [
                        'detail_type' => 'Cost',
                        'detail_index' => $index + 1,
                        'cost_description' => isset($cost['description']) ? $cost['description'] : '',
                        'cost_amount' => isset($cost['amount']) ? (float) $cost['amount'] : 0,
                        'photo_path' => '',
                    ]);
                    $detailedData->push($costData);
                }
            } else {
                // Add row without cost details
                $costData = array_merge($baseData, [
                    'detail_type' => 'Cost',
                    'detail_index' => 0,
                    'cost_description' => 'No cost details',
                    'cost_amount' => 0,
                    'photo_path' => '',
                ]);
                $detailedData->push($costData);
            }

            // Add photo details with absolute paths
            if ($log->photos && $log->photos->count() > 0) {
                foreach ($log->photos as $index => $photo) {
                    $photoData = array_merge($baseData, [
                        'detail_type' => 'Photo',
                        'detail_index' => $index + 1,
                        'cost_description' => '',
                        'cost_amount' => 0,
                        'photo_path' => url('storage/' . $photo->photo_path), // Absolute URL
                    ]);
                    $detailedData->push($photoData);
                }
            } else {
                // Add row without photos
                $photoData = array_merge($baseData, [
                    'detail_type' => 'Photo',
                    'detail_index' => 0,
                    'cost_description' => '',
                    'cost_amount' => 0,
                    'photo_path' => 'No photos',
                ]);
                $detailedData->push($photoData);
            }
        }

        return $detailedData;
    }

    public function headings(): array
    {
        return [
            'Log ID',
            'Tanggal Laporan',
            'Waktu Laporan',
            'Unit',
            'Rute',
            'Pengemudi',
            'Deskripsi',
            'Tipe',
            'Suku Cadang',
            'Kategori',
            'Sumber Suku Cadang',
            'Status',
            'Dalam Jadwal',
            'Tipe Detail',
            'Index Detail',
            'Deskripsi Biaya',
            'Jumlah Biaya',
            'Path Foto (Absolute)',
            'Tanggal Dibuat',
            'Tanggal Diperbarui'
        ];
    }

    public function map($item): array
    {
        return [
            $item['log_id'],
            $item['date_reported'] ? $item['date_reported']->format('d/m/Y') : '',
            $item['time_reported'] ? $item['time_reported']->format('H:i') : '',
            $item['unit'],
            $item['route'],
            $item['driver'],
            $item['description'],
            ucfirst($item['type']),
            $item['parts'],
            $item['category'] ? ucfirst($item['category']) : '',
            $item['source_of_sparepart'],
            ucfirst(str_replace('_', ' ', $item['status'])),
            $item['on_schedule'] ? 'Ya' : 'Tidak',
            $item['detail_type'],
            $item['detail_index'],
            $item['cost_description'],
            $item['cost_amount'] > 0 ? 'Rp ' . number_format($item['cost_amount'], 0, ',', '.') : '',
            $item['photo_path'],
            $item['created_at'] ? $item['created_at']->format('d/m/Y H:i') : '',
            $item['updated_at'] ? $item['updated_at']->format('d/m/Y H:i') : ''
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as header
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '2E8B57'],
                ],
            ],
            // Add borders to all cells
            'A1:T1000' => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ],
        ];
    }
}
