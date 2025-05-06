<?php

namespace App\Exports;

use App\Models\KilometerReport;
use App\Models\Route;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Carbon\Carbon;

class KilometerReportsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $startDate;
    protected $endDate;
    protected $routeGroup;
    protected $routes;
    protected $dates;
    protected $reports;
    protected $reportsByRouteUnitDate;

    public function __construct($startDate, $endDate, $routeGroup = 'all')
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->routeGroup = $routeGroup;
        
        // Load data
        $this->loadData();
    }
    
    /**
     * Load all necessary data for the export
     */
    protected function loadData()
    {
        // Get all dates in the range
        $this->dates = [];
        $currentDate = Carbon::parse($this->startDate);
        $lastDate = Carbon::parse($this->endDate);
        
        while ($currentDate->lte($lastDate)) {
            $this->dates[] = $currentDate->format('Y-m-d');
            $currentDate->addDay();
        }
        
        // Get routes based on group filter
        $routesQuery = Route::with(['units' => function($query) {
            $query->orderBy('unit_number');
        }])->orderBy('route_number');
        
        if ($this->routeGroup !== 'all') {
            $routesQuery->where('route_number', 'like', $this->routeGroup . '%');
        }
        
        $this->routes = $routesQuery->get();
        
        // Get all kilometer reports for the date range
        $this->reports = KilometerReport::with(['unit', 'route'])
            ->whereBetween('date', [$this->startDate, $this->endDate]);
            
        if ($this->routeGroup !== 'all') {
            $this->reports = $this->reports->whereHas('route', function($query) {
                $query->where('route_number', 'like', $this->routeGroup . '%');
            });
        }
        
        $this->reports = $this->reports->get();
        
        // Create a structured array of reports by route, unit, and date for easier access
        $this->reportsByRouteUnitDate = [];
        foreach ($this->reports as $report) {
            if (!isset($this->reportsByRouteUnitDate[$report->route_id])) {
                $this->reportsByRouteUnitDate[$report->route_id] = [];
            }
            
            if (!isset($this->reportsByRouteUnitDate[$report->route_id][$report->unit_id])) {
                $this->reportsByRouteUnitDate[$report->route_id][$report->unit_id] = [];
            }
            
            $this->reportsByRouteUnitDate[$report->route_id][$report->unit_id][$report->date->format('Y-m-d')] = $report;
        }
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        // Create a collection with one row per unit per route
        $collection = collect();
        $counter = 1;

        foreach ($this->routes as $route) {
            foreach ($route->units as $unit) {
                $row = [
                    'no' => $counter++,
                    'route' => $route->name . ' (' . $route->route_number . ')',
                    'unit_number' => $unit->unit_number,
                    'unit_id' => $unit->id,
                    'route_id' => $route->id,
                    'dates' => []
                ];

                // Add kilometers for each date
                foreach ($this->dates as $date) {
                    $km = 0;
                    if (isset($this->reportsByRouteUnitDate[$route->id][$unit->id][$date])) {
                        $report = $this->reportsByRouteUnitDate[$route->id][$unit->id][$date];
                        $km = $report->kilometers;
                    }
                    
                    $row['dates'][$date] = $km;
                }

                $collection->push((object)$row);
            }
        }

        return $collection;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        $headings = [
            'No.',
            'Rute',
            'Nomor Unit',
        ];

        // Add date columns
        foreach ($this->dates as $date) {
            $headings[] = Carbon::parse($date)->format('d M');
        }

        // Add total column
        $headings[] = 'Total KM';

        return $headings;
    }

    /**
     * @param mixed $row
     * @return array
     */
    public function map($row): array
    {
        $data = [
            $row->no,
            $row->route,
            $row->unit_number,
        ];

        // Add kilometer values for each date
        $total = 0;
        foreach ($this->dates as $date) {
            $km = $row->dates[$date] ?? 0;
            $data[] = $km;
            $total += $km;
        }

        // Add total
        $data[] = $total;

        return $data;
    }

    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        $lastColumn = chr(67 + count($this->dates));
        
        // Add title
        $periodText = $this->startDate . ' - ' . $this->endDate;
        $title = "LAPORAN KILOMETER PERIODE $periodText";
        
        $sheet->mergeCells("A1:{$lastColumn}1");
        $sheet->setCellValue('A1', $title);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        
        // Style for headers
        $sheet->getStyle("A3:{$lastColumn}3")->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => 'E2EFDA',
                ],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
        ]);
        
        // Style for data cells
        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle("A4:{$lastColumn}{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
        ]);
        
        // Center align the No. column
        $sheet->getStyle("A3:A{$lastRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        
        // Right align the KM columns
        $sheet->getStyle("D3:{$lastColumn}{$lastRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        
        return [];
    }
}
