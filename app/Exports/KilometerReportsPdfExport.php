<?php

namespace App\Exports;

use App\Models\KilometerReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\View;

class KilometerReportsPdfExport
{
    protected $reports;
    protected $routes;
    protected $dates;
    protected $period;
    protected $reportsByRouteUnitDate;

    public function __construct($reports, $routes, $dates, $period, $reportsByRouteUnitDate)
    {
        $this->reports = $reports;
        $this->routes = $routes;
        $this->dates = $dates;
        $this->period = $period;
        $this->reportsByRouteUnitDate = $reportsByRouteUnitDate;
    }

    /**
     * Download the PDF file
     */
    public function download()
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
        $periodText = $this->period == 1 ? '1-15' : '16-' . Carbon::parse($this->dates[count($this->dates) - 1])->format('d');
        $monthYear = Carbon::parse($this->dates[0])->format('F Y');
        $title = "LAPORAN KILOMETER PERIODE $periodText $monthYear";

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
        return $pdf->download("laporan_kilometer_periode_{$this->period}_{$monthYear}.pdf");
    }
}
