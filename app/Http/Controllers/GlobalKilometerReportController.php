<?php

namespace App\Http\Controllers;

use App\Models\Driver;
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
        
        // Get all kilometer reports for the date range
        $reports = KilometerReport::with(['unit', 'route'])
            ->whereBetween('date', [$startDate, $endDate])
            ->get();
        
        // Get all drivers
        $drivers = Driver::active()->get()->keyBy('id');
        
        // Get all units with their drivers
        $units = Unit::with('drivers')->get()->keyBy('id');
        
        // Get all schedules for the date range to determine drivers per unit
        $schedules = Schedule::with(['driver', 'unit', 'route'])
            ->whereBetween('schedule_date', [$startDate, $endDate])
            ->get();
        
        // Organize all schedules by unit and date
        $schedulesByUnitDate = [];
        foreach ($schedules as $schedule) {
            $unitId = $schedule->unit_id;
            $date = $schedule->schedule_date->format('Y-m-d');
            
            if (!isset($schedulesByUnitDate[$unitId])) {
                $schedulesByUnitDate[$unitId] = [];
            }
            if (!isset($schedulesByUnitDate[$unitId][$date])) {
                $schedulesByUnitDate[$unitId][$date] = [];
            }
            
            $schedulesByUnitDate[$unitId][$date][] = $schedule;
        }
        
        // If we don't have any schedules, let's assign drivers from unit.drivers relationship
        if ($schedules->isEmpty()) {
            foreach ($units as $unit) {
                $unitId = $unit->id;
                $unitDrivers = $unit->drivers;
                
                if ($unitDrivers->isNotEmpty()) {
                    $schedulesByUnitDate[$unitId] = [];
                    
                    foreach ($dates as $date) {
                        $schedulesByUnitDate[$unitId][$date] = [];
                        
                        // Create fake schedules for each driver
                        foreach ($unitDrivers as $index => $driver) {
                            $fakeSchedule = new \stdClass();
                            $fakeSchedule->driver_id = $driver->id;
                            $fakeSchedule->unit_id = $unitId;
                            $fakeSchedule->shift = $index == 0 ? 'Pagi' : 'Siang';
                            $fakeSchedule->driver = $driver;
                            
                            $schedulesByUnitDate[$unitId][$date][] = $fakeSchedule;
                        }
                    }
                }
            }
        }
        
        // Organize reports by route, unit, driver, and date
        $reportsByRouteUnitDriverDate = [];
        $driverCountByRouteUnitDate = [];
        $routeTotals = [];
        $unitTotals = [];
        $driverTotals = [];
        $dateTotals = [];
        $routeUnitTotals = [];
        $routeDriverTotals = [];
        $grandTotal = 0;
        
        // Process kilometer reports
        foreach ($reports as $report) {
            $unitId = $report->unit_id;
            $routeId = $report->route_id;
            $date = $report->date->format('Y-m-d');
            $kilometers = $report->kilometers;
            
            // Initialize arrays if not set
            if (!isset($reportsByRouteUnitDriverDate[$routeId])) {
                $reportsByRouteUnitDriverDate[$routeId] = [];
            }
            if (!isset($reportsByRouteUnitDriverDate[$routeId][$unitId])) {
                $reportsByRouteUnitDriverDate[$routeId][$unitId] = [];
            }
            
            // Get schedules for this unit and date
            $unitSchedules = $schedulesByUnitDate[$unitId][$date] ?? [];
            $driverCount = count($unitSchedules);
            
            // If no drivers scheduled, try to get drivers from the unit's relationship
            if ($driverCount == 0 && isset($units[$unitId])) {
                $unitDrivers = $units[$unitId]->drivers;
                
                if ($unitDrivers->isNotEmpty()) {
                    // Create fake schedules for unit drivers
                    foreach ($unitDrivers as $index => $driver) {
                        $fakeSchedule = new \stdClass();
                        $fakeSchedule->driver_id = $driver->id;
                        $fakeSchedule->unit_id = $unitId;
                        $fakeSchedule->shift = $index == 0 ? 'Pagi' : 'Siang';
                        $fakeSchedule->driver = $driver;
                        
                        $unitSchedules[] = $fakeSchedule;
                    }
                    
                    $driverCount = count($unitSchedules);
                }
            }
            
            // If still no drivers, create a placeholder for the unit
            if ($driverCount == 0) {
                // Store unit total
                if (!isset($routeUnitTotals[$routeId])) {
                    $routeUnitTotals[$routeId] = [];
                }
                if (!isset($routeUnitTotals[$routeId][$unitId])) {
                    $routeUnitTotals[$routeId][$unitId] = 0;
                }
                $routeUnitTotals[$routeId][$unitId] += $kilometers;
                
                continue;
            }
            
            // Calculate kilometers per driver
            $kilometersPerDriver = $driverCount > 0 ? $kilometers / $driverCount : 0;
            
            // Store driver count for this unit and date
            if (!isset($driverCountByRouteUnitDate[$routeId])) {
                $driverCountByRouteUnitDate[$routeId] = [];
            }
            if (!isset($driverCountByRouteUnitDate[$routeId][$unitId])) {
                $driverCountByRouteUnitDate[$routeId][$unitId] = [];
            }
            $driverCountByRouteUnitDate[$routeId][$unitId][$date] = $driverCount;
            
            // Assign kilometers to each driver
            foreach ($unitSchedules as $schedule) {
                $driverId = $schedule->driver_id;
                
                // Store report data
                $reportsByRouteUnitDriverDate[$routeId][$unitId][$driverId][$date] = (object) [
                    'kilometers' => $kilometersPerDriver,
                    'notes' => $report->notes,
                    'original_kilometers' => $kilometers,
                    'driver_count' => $driverCount,
                    'shift' => $schedule->shift
                ];
                
                // Driver totals
                if (!isset($driverTotals[$driverId])) {
                    $driverTotals[$driverId] = 0;
                }
                $driverTotals[$driverId] += $kilometersPerDriver;
                
                // Route-Driver totals
                if (!isset($routeDriverTotals[$routeId])) {
                    $routeDriverTotals[$routeId] = [];
                }
                if (!isset($routeDriverTotals[$routeId][$driverId])) {
                    $routeDriverTotals[$routeId][$driverId] = 0;
                }
                $routeDriverTotals[$routeId][$driverId] += $kilometersPerDriver;
            }
            
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
        
        // Get all holidays for the date range
        $holidays = Holiday::whereBetween('date', [$startDate, $endDate])
            ->get()
            ->keyBy(function ($holiday) {
                return $holiday->date->format('Y-m-d');
            });
        
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
        $maintenanceUnitsByDate = [];
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
        $period = $request->input('period', 1);
        $group = $request->input('group', 'all');
        
        $title = "Laporan Kilometer Global - " . ($period == 1 ? "Periode 1" : "Periode 2") . " " . Carbon::now()->format('F Y');
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
        $period = $request->input('period', 1);
        $group = $request->input('group', 'all');
        
        $title = "Laporan Kilometer Global - " . ($period == 1 ? "Periode 1" : "Periode 2") . " " . Carbon::now()->format('F Y');
        if ($group != 'all') {
            $title .= " - Rute " . $group;
        }
        
        // TODO: Implement PDF export for global kilometer reports
        
        return redirect()->back()->with('info', 'Export PDF functionality will be implemented soon.');
    }
}
