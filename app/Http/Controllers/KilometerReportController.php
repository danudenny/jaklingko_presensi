<?php

namespace App\Http\Controllers;

use App\Models\KilometerReport;
use App\Models\Unit;
use App\Models\Route;
use App\Exports\KilometerReportsExport;
use App\Exports\KilometerReportsPdfExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class KilometerReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $period = $request->input('period', 1); // Default to period 1
        
        // Determine date ranges based on period
        $today = Carbon::now();
        $currentMonth = $today->copy()->startOfMonth();
        
        if ($period == 1) {
            // Period 1: 1st to 15th of the month
            $startDate = $currentMonth->copy()->format('Y-m-d');
            $endDate = $currentMonth->copy()->addDays(14)->format('Y-m-d');
        } else {
            // Period 2: 16th to end of month
            $startDate = $currentMonth->copy()->addDays(15)->format('Y-m-d');
            $endDate = $currentMonth->copy()->endOfMonth()->format('Y-m-d');
        }
        
        // Get all routes with their assigned units
        $routes = Route::with('units')->orderBy('route_number')->get();
        
        // Get all dates in the range
        $dates = [];
        $currentDate = Carbon::parse($startDate);
        $lastDate = Carbon::parse($endDate);
        
        while ($currentDate->lte($lastDate)) {
            $dates[] = $currentDate->format('Y-m-d');
            $currentDate->addDay();
        }
        
        // Get all kilometer reports for the date range
        $reports = KilometerReport::with(['unit', 'route'])
            ->whereBetween('date', [$startDate, $endDate])
            ->get();
        
        // Group reports by route, unit, and date
        $reportsByRouteUnitDate = [];
        foreach ($reports as $report) {
            $routeId = $report->route_id;
            $unitId = $report->unit_id;
            $date = $report->date->format('Y-m-d');
            
            if (!isset($reportsByRouteUnitDate[$routeId])) {
                $reportsByRouteUnitDate[$routeId] = [];
            }
            
            if (!isset($reportsByRouteUnitDate[$routeId][$unitId])) {
                $reportsByRouteUnitDate[$routeId][$unitId] = [];
            }
            
            $reportsByRouteUnitDate[$routeId][$unitId][$date] = $report;
        }
        
        // Calculate totals
        $routeTotals = [];
        $unitTotals = [];
        $dateTotals = [];
        $routeUnitTotals = [];
        $grandTotal = 0;
        
        foreach ($reports as $report) {
            $routeId = $report->route_id;
            $unitId = $report->unit_id;
            $date = $report->date->format('Y-m-d');
            $kilometers = $report->kilometers;
            
            // Route totals
            if (!isset($routeTotals[$routeId])) {
                $routeTotals[$routeId] = 0;
            }
            $routeTotals[$routeId] += $kilometers;
            
            // Unit totals
            if (!isset($unitTotals[$unitId])) {
                $unitTotals[$unitId] = 0;
            }
            $unitTotals[$unitId] += $kilometers;
            
            // Date totals
            if (!isset($dateTotals[$date])) {
                $dateTotals[$date] = 0;
            }
            $dateTotals[$date] += $kilometers;
            
            // Route-Unit totals
            if (!isset($routeUnitTotals[$routeId])) {
                $routeUnitTotals[$routeId] = [];
            }
            if (!isset($routeUnitTotals[$routeId][$unitId])) {
                $routeUnitTotals[$routeId][$unitId] = 0;
            }
            $routeUnitTotals[$routeId][$unitId] += $kilometers;
            
            // Grand total
            $grandTotal += $kilometers;
        }
        
        return view('modules.admin.kilometer-reports.index', compact(
            'routes',
            'dates', 
            'reportsByRouteUnitDate', 
            'routeTotals', 
            'unitTotals', 
            'dateTotals',
            'routeUnitTotals',
            'grandTotal',
            'startDate',
            'endDate',
            'period'
        ));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'unit_id' => 'required|exists:units,id',
            'route_id' => 'required|exists:routes,id',
            'date' => 'required|date',
            'kilometers' => 'required|numeric|min:0|max:999.9',
        ]);
        
        // Check if record already exists
        $existingReport = KilometerReport::where('unit_id', $request->unit_id)
            ->where('route_id', $request->route_id)
            ->where('date', $request->date)
            ->first();
            
        if ($existingReport) {
            // Update existing record
            $existingReport->update([
                'kilometers' => $request->kilometers,
                'notes' => $request->notes,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Data kilometer berhasil diperbarui',
                'report' => $existingReport
            ]);
        } else {
            // Create new record
            $report = KilometerReport::create([
                'unit_id' => $request->unit_id,
                'route_id' => $request->route_id,
                'date' => $request->date,
                'kilometers' => $request->kilometers,
                'notes' => $request->notes,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Data kilometer berhasil disimpan',
                'report' => $report
            ]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, $unitId)
    {
        $unit = Unit::findOrFail($unitId);
        $period = $request->input('period', 1); // Default to period 1
        
        // Determine date ranges based on period
        $today = Carbon::now();
        $currentMonth = $today->copy()->startOfMonth();
        
        if ($period == 1) {
            // Period 1: 1st to 15th of the month
            $startDate = $currentMonth->copy()->format('Y-m-d');
            $endDate = $currentMonth->copy()->addDays(14)->format('Y-m-d');
        } else {
            // Period 2: 16th to end of month
            $startDate = $currentMonth->copy()->addDays(15)->format('Y-m-d');
            $endDate = $currentMonth->copy()->endOfMonth()->format('Y-m-d');
        }
        
        // Get all dates in the range
        $dates = [];
        $currentDate = Carbon::parse($startDate);
        $lastDate = Carbon::parse($endDate);
        
        while ($currentDate->lte($lastDate)) {
            $dates[] = $currentDate->format('Y-m-d');
            $currentDate->addDay();
        }
        
        // Get all routes for this unit
        $routes = $unit->routes;
        
        // Get all kilometer reports for this unit and date range
        $reports = KilometerReport::with(['route'])
            ->where('unit_id', $unitId)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();
        
        // Group reports by route and date
        $reportsByRouteAndDate = [];
        foreach ($reports as $report) {
            $routeId = $report->route_id;
            $date = $report->date->format('Y-m-d');
            
            if (!isset($reportsByRouteAndDate[$routeId])) {
                $reportsByRouteAndDate[$routeId] = [];
            }
            
            $reportsByRouteAndDate[$routeId][$date] = $report;
        }
        
        // Calculate totals
        $routeTotals = [];
        $dateTotals = [];
        $grandTotal = 0;
        
        foreach ($reports as $report) {
            $routeId = $report->route_id;
            $date = $report->date->format('Y-m-d');
            $kilometers = $report->kilometers;
            
            // Route totals
            if (!isset($routeTotals[$routeId])) {
                $routeTotals[$routeId] = 0;
            }
            $routeTotals[$routeId] += $kilometers;
            
            // Date totals
            if (!isset($dateTotals[$date])) {
                $dateTotals[$date] = 0;
            }
            $dateTotals[$date] += $kilometers;
            
            // Grand total
            $grandTotal += $kilometers;
        }
        
        // Get related unit problems
        $unitProblems = $unit->unitProblems()
            ->whereBetween('date_reported', [$startDate, $endDate])
            ->orderBy('date_reported')
            ->get();
        
        // Get related schedules
        $schedules = $unit->schedules()
            ->whereBetween('schedule_date', [$startDate, $endDate])
            ->orderBy('schedule_date')
            ->get();
        
        return view('modules.admin.kilometer-reports.show', compact(
            'unit', 
            'routes', 
            'dates', 
            'reportsByRouteAndDate', 
            'routeTotals', 
            'dateTotals', 
            'grandTotal',
            'startDate',
            'endDate',
            'unitProblems',
            'schedules',
            'period'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    /**
     * Export kilometer reports to Excel
     */
    public function exportExcel(Request $request)
    {
        $period = $request->input('period', 1); // Default to period 1
        
        // Determine date ranges based on period
        $today = Carbon::now();
        $currentMonth = $today->copy()->startOfMonth();
        
        if ($period == 1) {
            // Period 1: 1st to 15th of the month
            $startDate = $currentMonth->copy()->format('Y-m-d');
            $endDate = $currentMonth->copy()->addDays(14)->format('Y-m-d');
        } else {
            // Period 2: 16th to end of month
            $startDate = $currentMonth->copy()->addDays(15)->format('Y-m-d');
            $endDate = $currentMonth->copy()->endOfMonth()->format('Y-m-d');
        }
        
        // Get all routes with their assigned units through the unit_routes relationship
        $routes = Route::with(['units' => function($query) {
            $query->orderBy('unit_number');
        }])->orderBy('route_number')->get();
        
        // Get all dates in the range
        $dates = [];
        $currentDate = Carbon::parse($startDate);
        $lastDate = Carbon::parse($endDate);
        
        while ($currentDate->lte($lastDate)) {
            $dates[] = $currentDate->format('Y-m-d');
            $currentDate->addDay();
        }
        
        // Get all kilometer reports for the date range
        $reports = KilometerReport::with(['unit', 'route'])
            ->whereBetween('date', [$startDate, $endDate])
            ->get();
            
        // Create a structured array of reports by route, unit, and date for easier access
        $reportsByRouteUnitDate = [];
        foreach ($reports as $report) {
            $reportsByRouteUnitDate[$report->route_id][$report->unit_id][$report->date] = $report;
        }
            
        // Generate filename
        $periodText = $period == 1 ? '1-15' : '16-' . Carbon::parse($endDate)->format('d');
        $monthYear = Carbon::parse($startDate)->format('F_Y');
        $filename = "laporan_kilometer_periode_{$periodText}_{$monthYear}.xlsx";
        
        return Excel::download(new KilometerReportsExport($reports, $routes, $dates, $period, $reportsByRouteUnitDate), $filename);
    }
    
    /**
     * Export kilometer reports to PDF
     */
    public function exportPdf(Request $request)
    {
        $period = $request->input('period', 1); // Default to period 1
        
        // Determine date ranges based on period
        $today = Carbon::now();
        $currentMonth = $today->copy()->startOfMonth();
        
        if ($period == 1) {
            // Period 1: 1st to 15th of the month
            $startDate = $currentMonth->copy()->format('Y-m-d');
            $endDate = $currentMonth->copy()->addDays(14)->format('Y-m-d');
        } else {
            // Period 2: 16th to end of month
            $startDate = $currentMonth->copy()->addDays(15)->format('Y-m-d');
            $endDate = $currentMonth->copy()->endOfMonth()->format('Y-m-d');
        }
        
        // Get all routes with their assigned units through the unit_routes relationship
        $routes = Route::with(['units' => function($query) {
            $query->orderBy('unit_number');
        }])->orderBy('route_number')->get();
        
        // Get all dates in the range
        $dates = [];
        $currentDate = Carbon::parse($startDate);
        $lastDate = Carbon::parse($endDate);
        
        while ($currentDate->lte($lastDate)) {
            $dates[] = $currentDate->format('Y-m-d');
            $currentDate->addDay();
        }
        
        // Get all kilometer reports for the date range
        $reports = KilometerReport::with(['unit', 'route'])
            ->whereBetween('date', [$startDate, $endDate])
            ->get();
            
        // Create a structured array of reports by route, unit, and date for easier access
        $reportsByRouteUnitDate = [];
        foreach ($reports as $report) {
            $reportsByRouteUnitDate[$report->route_id][$report->unit_id][$report->date] = $report;
        }
            
        $pdfExport = new KilometerReportsPdfExport($reports, $routes, $dates, $period, $reportsByRouteUnitDate);
        return $pdfExport->download();
    }
}
