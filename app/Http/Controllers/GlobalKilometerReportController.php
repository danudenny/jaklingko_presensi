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
    public function index(Request $request)
    {
        $period = (int)$request->input('period', 1); // Default to period 1
        $month = (int)$request->input('month', Carbon::now()->month);
        $year = (int)$request->input('year', Carbon::now()->year);
        
        $currentMonth = Carbon::createFromDate($year, $month, 1);
        
        if ($period == 1) {
            $startDate = $currentMonth->copy()->format('Y-m-d');
            $endDate = $currentMonth->copy()->addDays(14)->format('Y-m-d');
        } else {
            $startDate = $currentMonth->copy()->addDays(15)->format('Y-m-d');
            $endDate = $currentMonth->copy()->endOfMonth()->format('Y-m-d');
        }
        
        $hasReports = GlobalKilometerReport::where('period', $period)
            ->where('month', $month)
            ->where('year', $year)
            ->exists();
        
        $routeGroups = Route::select('route_number')
            ->distinct()
            ->whereNotNull('route_number')
            ->where('route_number', '!=', '')
            ->orderBy('route_number')
            ->pluck('route_number')
            ->toArray();
            
        $defaultGroup = !empty($routeGroups) ? $routeGroups[0] : 'all';
        $activeRouteGroup = $request->input('group', $defaultGroup);
        
        $routeGroups[] = 'all';
        
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
        
        $dates = [];
        $currentDate = Carbon::parse($startDate);
        $lastDate = Carbon::parse($endDate);
        
        while ($currentDate->lte($lastDate)) {
            $dates[] = $currentDate->format('Y-m-d');
            $currentDate->addDay();
        }
        
        $dates = [];
        $currentDate = Carbon::parse($startDate);
        $lastDate = Carbon::parse($endDate);
        
        while ($currentDate->lte($lastDate)) {
            $dates[] = $currentDate->format('Y-m-d');
            $currentDate->addDay();
        }
        
        $drivers = Driver::active()->get()->keyBy('id');
        $units = Unit::with('drivers')->get()->keyBy('id');
        
        $routeTotals = [];
        $unitTotals = [];
        $driverTotals = [];
        $dateTotals = [];
        $routeUnitTotals = [];
        $routeDriverTotals = [];
        
        $reportsByRouteUnitDriverDate = [];
        $driverCountByRouteUnitDate = [];
        $maintenanceUnitsByDate = [];
        $grandTotal = 0;
        
        if ($hasReports) {
            $globalReports = GlobalKilometerReport::with(['driver', 'unit', 'route'])
                ->where('period', $period)
                ->where('month', $month)
                ->where('year', $year);
                
            if ($activeRouteGroup !== 'all') {
                $globalReports->whereHas('route', function ($query) use ($activeRouteGroup) {
                    $query->where('route_number', $activeRouteGroup);
                });
            }
            
            $globalReports = $globalReports->get();
            
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
    
    /**
     * Reset all global kilometer reports data by truncating the table.
     * 
     * @return \Illuminate\Http\RedirectResponse
     */
    public function reset()
    {
        try {
            // Truncate the global_kilometer_reports table
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            GlobalKilometerReport::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            
            return redirect()->route('global-kilometer-reports.index')
                ->with('success', 'Data laporan kilometer global berhasil direset.');
        } catch (\Exception $e) {
            return redirect()->route('global-kilometer-reports.index')
                ->with('error', 'Gagal mereset data: ' . $e->getMessage());
        }
    }
}
