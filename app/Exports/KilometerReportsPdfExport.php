<?php

namespace App\Exports;

use App\Models\KilometerReport;
use App\Models\Route;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\View;

class KilometerReportsPdfExport
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
     * Download the PDF file
     */
    public function download($filename = null)
    {
        // Format dates for display
        $formattedDates = [];
        foreach ($this->dates as $date) {
            $formattedDates[] = Carbon::parse($date)->format('d M');
        }

        // Prepare data for the view
        $data = [];
        $counter = 1;

        foreach ($this->routes as $route) {
            $routeData = [
                'route' => $route,
                'units' => []
            ];

            foreach ($route->units as $unit) {
                $unitData = [
                    'no' => $counter++,
                    'unit' => $unit,
                    'kilometers' => [],
                    'total' => 0
                ];

                // Add kilometers for each date
                foreach ($this->dates as $date) {
                    $km = 0;
                    if (isset($this->reportsByRouteUnitDate[$route->id][$unit->id][$date])) {
                        $report = $this->reportsByRouteUnitDate[$route->id][$unit->id][$date];
                        $km = $report->kilometers;
                    }
                    
                    $unitData['kilometers'][$date] = $km;
                    $unitData['total'] += $km;
                }

                $routeData['units'][] = $unitData;
            }

            $data[] = $routeData;
        }

        // Set the period text
        $monthYear = Carbon::parse($this->startDate)->format('F Y');
        $title = "LAPORAN KILOMETER PERIODE $monthYear";

        // Generate the PDF
        $pdf = PDF::loadView('exports.kilometer-reports-pdf', [
            'data' => $data,
            'dates' => $this->dates,
            'formattedDates' => $formattedDates,
            'title' => $title
        ]);

        // Set paper to landscape
        $pdf->setPaper('a4', 'landscape');

        // Download the file
        return $pdf->download($filename ?: "laporan_kilometer_periode_{$monthYear}.pdf");
    }
}
