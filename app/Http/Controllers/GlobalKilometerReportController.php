<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\GlobalKilometerReport;
use App\Models\Holiday;
use App\Models\KilometerReport;
use App\Models\MaintenanceLog;
use App\Models\Route;
use App\Models\Schedule;
use App\Models\Unit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class GlobalKilometerReportController extends Controller
{
    /**
     * Display a listing of the global kilometer reports.
     */
    public function index(Request $request)
    {
        $period = (int)$request->input('period', 1); // Default to period 1
        $month = (int)$request->input('month', Carbon::now()->month);
        $year = (int)$request->input('year', Carbon::now()->year);
        
        // Determine date ranges based on period
        $currentMonth = Carbon::createFromDate($year, $month, 1);
        
        if ($period == 1) {
            // Period 1: 1st to 15th of the month
            $startDate = $currentMonth->copy()->format('Y-m-d');
            $endDate = $currentMonth->copy()->addDays(14)->format('Y-m-d');
        } else {
            // Period 2: 16th to end of month
            $startDate = $currentMonth->copy()->addDays(15)->format('Y-m-d');
            $endDate = $currentMonth->copy()->endOfMonth()->format('Y-m-d');
        }
        
        // Check if we have any global kilometer reports for this period
        $hasReports = GlobalKilometerReport::where('period', $period)
            ->where('month', $month)
            ->where('year', $year)
            ->exists();
        
        // Get all route groups for the tabs
        $routeGroups = Route::select('route_number')
            ->distinct()
            ->whereNotNull('route_number')
            ->where('route_number', '!=', '')
            ->orderBy('route_number')
            ->pluck('route_number')
            ->toArray();
            
        // Default to first route group if available, otherwise use 'all'
        $defaultGroup = !empty($routeGroups) ? $routeGroups[0] : 'all';
        $activeRouteGroup = $request->input('group', $defaultGroup);
        
        // Add 'all' to the end of route groups
        $routeGroups[] = 'all';
        
        // Get all routes
        if ($activeRouteGroup !== 'all') {
            $routes = Route::where('route_number', $activeRouteGroup)
                ->with('units')
                ->orderBy('route_number')
                ->get();
        } else {
            $routes = Route::with('units')
                ->orderBy('route_number')
                ->get();
        }
        
        // Get all dates in the range
        $dates = [];
        $currentDate = Carbon::parse($startDate);
        $lastDate = Carbon::parse($endDate);
        
        while ($currentDate->lte($lastDate)) {
            $dates[] = $currentDate->format('Y-m-d');
            $currentDate->addDay();
        }
        
        // Get all dates in the range
        $dates = [];
        $currentDate = Carbon::parse($startDate);
        $lastDate = Carbon::parse($endDate);
        
        while ($currentDate->lte($lastDate)) {
            $dates[] = $currentDate->format('Y-m-d');
            $currentDate->addDay();
        }
        
        // Get all drivers
        $drivers = Driver::active()->get()->keyBy('id');
        
        // Get all units with their drivers
        $units = Unit::with('drivers')->get()->keyBy('id');
        
        // Organize reports by route, unit, driver, and date
        $routeTotals = [];
        $unitTotals = [];
        $driverTotals = [];
        $dateTotals = [];
        $routeUnitTotals = [];
        $routeDriverTotals = [];
        
        // Initialize empty arrays for the view if there are no reports
        $reportsByRouteUnitDriverDate = [];
        $driverCountByRouteUnitDate = [];
        $maintenanceUnitsByDate = [];
        $grandTotal = 0;
        
        // If we have global kilometer reports, use them
        if ($hasReports) {
            // Get all global kilometer reports for this period
            $globalReports = GlobalKilometerReport::with(['driver', 'unit', 'route'])
                ->where('period', $period)
                ->where('month', $month)
                ->where('year', $year);
                
            // If filtering by route group
            if ($activeRouteGroup !== 'all') {
                $globalReports->whereHas('route', function ($query) use ($activeRouteGroup) {
                    $query->where('route_number', $activeRouteGroup);
                });
            }
            
            $globalReports = $globalReports->get();
            
            // Process global kilometer reports
            foreach ($globalReports as $report) {
                $unitId = $report->unit_id;
                $routeId = $report->route_id;
                $driverId = $report->driver_id;
                $date = $report->report_date->format('Y-m-d');
                $kilometers = $report->kilometers;
                $driverCount = $report->driver_count;
                
                // Initialize arrays if not set
                if (!isset($reportsByRouteUnitDriverDate[$routeId])) {
                    $reportsByRouteUnitDriverDate[$routeId] = [];
                }
                if (!isset($reportsByRouteUnitDriverDate[$routeId][$unitId])) {
                    $reportsByRouteUnitDriverDate[$routeId][$unitId] = [];
                }
                if (!isset($reportsByRouteUnitDriverDate[$routeId][$unitId][$driverId])) {
                    $reportsByRouteUnitDriverDate[$routeId][$unitId][$driverId] = [];
                }
                
                // Store report data
                $reportsByRouteUnitDriverDate[$routeId][$unitId][$driverId][$date] = (object) [
                    'kilometers' => $kilometers,
                    'notes' => $report->notes,
                    'original_kilometers' => $kilometers * $driverCount, // Original total kilometers
                    'driver_count' => $driverCount,
                    'shift' => 'All' // We don't track shift in global reports
                ];
                
                // Store driver count for this unit and date
                if (!isset($driverCountByRouteUnitDate[$routeId])) {
                    $driverCountByRouteUnitDate[$routeId] = [];
                }
                if (!isset($driverCountByRouteUnitDate[$routeId][$unitId])) {
                    $driverCountByRouteUnitDate[$routeId][$unitId] = [];
                }
                $driverCountByRouteUnitDate[$routeId][$unitId][$date] = $driverCount;
                
                // Driver totals
                if (!isset($driverTotals[$driverId])) {
                    $driverTotals[$driverId] = 0;
                }
                $driverTotals[$driverId] += $kilometers;
                
                // Route-Driver totals
                if (!isset($routeDriverTotals[$routeId])) {
                    $routeDriverTotals[$routeId] = [];
                }
                if (!isset($routeDriverTotals[$routeId][$driverId])) {
                    $routeDriverTotals[$routeId][$driverId] = 0;
                }
                $routeDriverTotals[$routeId][$driverId] += $kilometers;
                
                // Calculate original kilometers (before driver division)
                $originalKilometers = $kilometers * $driverCount;
                
                // Route totals (based on original kilometers)
                if (!isset($routeTotals[$routeId])) {
                    $routeTotals[$routeId] = 0;
                }
                $routeTotals[$routeId] += $originalKilometers;
                
                // Unit totals (based on original kilometers)
                if (!isset($unitTotals[$unitId])) {
                    $unitTotals[$unitId] = 0;
                }
                $unitTotals[$unitId] += $originalKilometers;
                
                // Date totals (based on original kilometers)
                if (!isset($dateTotals[$date])) {
                    $dateTotals[$date] = 0;
                }
                $dateTotals[$date] += $originalKilometers;
                
                // Route-Unit totals (based on original kilometers)
                if (!isset($routeUnitTotals[$routeId])) {
                    $routeUnitTotals[$routeId] = [];
                }
                if (!isset($routeUnitTotals[$routeId][$unitId])) {
                    $routeUnitTotals[$routeId][$unitId] = 0;
                }
                $routeUnitTotals[$routeId][$unitId] += $originalKilometers;
                
                // Grand total (based on original kilometers)
                $grandTotal += $originalKilometers;
            }
        } 
        // Don't redirect, just continue and show empty table with a flash message
        
        // Get all holidays for the date range
        $holidays = Holiday::whereBetween('date', [$startDate, $endDate])
            ->get()
            ->keyBy(function ($holiday) {
                return $holiday->date->format('Y-m-d');
            });
        
        // Get maintenance units data    
        $maintenanceUnitsByDate = [];
        
        // Get all maintenance logs for the date range
        $maintenanceLogs = MaintenanceLog::whereBetween('date_reported', [$startDate, $endDate])
            ->orWhere(function ($query) use ($startDate, $endDate) {
                $query->where('date_reported', '<=', $endDate)
                    ->where(function ($q) use ($startDate) {
                        $q->where('date_reported', '>=', $startDate)
                            ->orWhereNull('date_reported');
                    });
            })
            ->get();
        
        // Create a lookup for units under maintenance on specific dates
        foreach ($dates as $date) {
            $maintenanceUnitsByDate[$date] = [];
            foreach ($maintenanceLogs as $log) {
                $logStartDate = Carbon::parse($log->date_reported);
                
                // For maintenance logs, we'll consider the unit under maintenance on the reported date
                if ($logStartDate->format('Y-m-d') === $date && $log->status !== 'completed') {
                    $maintenanceUnitsByDate[$date][] = $log->unit_id;
                }
            }
        }
            
        // Add a flash message if no reports exist
        if (!$hasReports) {
            session()->flash('info', 'Tidak ada laporan kilometer global untuk periode ini. Silakan generate laporan terlebih dahulu.');
        }
        
        return view('modules.admin.global-kilometer-reports.index', compact(
            'routes',
            'dates', 
            'reportsByRouteUnitDriverDate', 
            'routeTotals', 
            'unitTotals',
            'driverTotals',
            'dateTotals',
            'routeUnitTotals',
            'routeDriverTotals',
            'grandTotal',
            'startDate',
            'endDate',
            'period',
            'routeGroups',
            'activeRouteGroup',
            'drivers',
            'holidays',
            'maintenanceUnitsByDate'
        ));
    }
    
    /**
     * Export global kilometer reports to Excel.
     */
    public function exportExcel(Request $request)
    {
        $period = (int)$request->input('period', 1);
        $group = $request->input('group', 'all');
        $month = (int)$request->input('month', Carbon::now()->month);
        $year = (int)$request->input('year', Carbon::now()->year);
        
        $date = Carbon::createFromDate($year, $month, 1);
        $title = "Laporan Kilometer Global - " . ($period == 1 ? "Periode 1" : "Periode 2") . " " . $date->format('F Y');
        if ($group != 'all') {
            $title .= " - Rute " . $group;
        }
        
        // TODO: Implement Excel export for global kilometer reports
        
        return redirect()->back()->with('info', 'Export Excel functionality will be implemented soon.');
    }
    
    /**
     * Export global kilometer reports to PDF.
     */
    public function exportPdf(Request $request)
    {
        $period = (int)$request->input('period', 1);
        $group = $request->input('group', 'all');
        $month = (int)$request->input('month', Carbon::now()->month);
        $year = (int)$request->input('year', Carbon::now()->year);
        
        $date = Carbon::createFromDate($year, $month, 1);
        $title = "Laporan Kilometer Global - " . ($period == 1 ? "Periode 1" : "Periode 2") . " " . $date->format('F Y');
        if ($group != 'all') {
            $title .= " - Rute " . $group;
        }
        
        // TODO: Implement PDF export for global kilometer reports
        
        return redirect()->back()->with('info', 'Export PDF functionality will be implemented soon.');
    }
}
