<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Models\Driver;
use App\Models\Holiday;
use App\Models\Route;
use App\Models\Schedule;
use App\Models\Unit;
use App\Models\UnitRenops;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use App\Services\ScheduleGeneratorService;
use App\Exports\SchedulesExport;
use App\Exports\SchedulesPdfExport;
use App\Exports\SchedulesMatrixPdfExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;

class ScheduleController extends Controller
{
    /**
     * Display a consolidated schedule view organized by routes, units and drivers
     * 
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // Get selected month, year and period from request or use current date
        $month = $request->query('month', Carbon::now()->month);
        $year = $request->query('year', Carbon::now()->year);
        $period = $request->query('period', 1); // Default to first period (1-15)
        $selectedRoute = $request->query('route', null);
        $selectedDriver = $request->query('driver', null);
        $selectedUnit = $request->query('unit', null);
        $selectedShift = $request->query('shift', null);
        
        // Calculate date range for the selected period
        if ($period == 1) {
            $startDate = Carbon::createFromDate($year, $month, 1);
            $endDate = Carbon::createFromDate($year, $month, 15);
        } else {
            $startDate = Carbon::createFromDate($year, $month, 16);
            $endDate = Carbon::createFromDate($year, $month)->endOfMonth();
        }
        
        // Create array of dates in the range
        $dateRange = [];
        $currentDate = clone $startDate;
        
        while ($currentDate->lte($endDate)) {
            $dateRange[] = $currentDate->format('Y-m-d');
            $currentDate->addDay();
        }
        
        // Get holidays in the date range
        $holidaysCollection = Holiday::whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get();
            
        // Format the dates properly for use in the view
        $holidays = [];
        foreach ($holidaysCollection as $holiday) {
            $formattedDate = $holiday->date->format('Y-m-d');
            $holidays[$formattedDate] = $holiday->name;
        }
        
        // Log the holidays for debugging
        \Illuminate\Support\Facades\Log::info('Holidays loaded for schedule display:', $holidays);
        
        // Get units in renops (not operating) for the date range
        $unitRenopsCollection = UnitRenops::whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get();
            
        // Format the unit renops data for easy lookup in the view
        // Structure: [date][unit_id] = true
        $unitRenops = [];
        foreach ($unitRenopsCollection as $renop) {
            $formattedDate = $renop->date->format('Y-m-d');
            if (!isset($unitRenops[$formattedDate])) {
                $unitRenops[$formattedDate] = [];
            }
            $unitRenops[$formattedDate][$renop->unit_id] = true;
        }
        
        // Log the unit renops for debugging
        \Illuminate\Support\Facades\Log::info('Unit renops loaded for schedule display:', ['count' => count($unitRenopsCollection)]);

        // Get all routes, drivers and units to build the dropdown filters
        $routes = Route::orderBy('route_number')->get();
        $drivers = Driver::where('status', 'aktif')
            ->orderByRaw('CASE WHEN type = "batangan" THEN 0 ELSE 1 END')
            ->orderBy('name')
            ->get();
        $units = Unit::where('status', 'aktif')
            ->orderBy('unit_number')
            ->get();
        
        // Build query for schedules based on filters
        $query = Schedule::with(['driver', 'backupDriver', 'route', 'unit'])
            ->whereBetween('schedule_date', [
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d')
            ]);
            
        // Apply filters if selected
        if ($selectedRoute) {
            $query->where('route_id', $selectedRoute);
        }
        
        if ($selectedDriver) {
            $query->where(function($query) use ($selectedDriver) {
                $query->where('driver_id', $selectedDriver)
                      ->orWhere('backup_driver_id', $selectedDriver);
            });
        }
        
        if ($selectedUnit) {
            $query->where('unit_id', $selectedUnit);
        }
        
        if ($selectedShift) {
            $query->where('shift', $selectedShift);
        }
            
        // Get schedules
        $schedules = $query->get();
        
        // Calculate statistics
        $totalAssignments = $schedules->count();
        $uniqueDriversCount = $schedules->pluck('driver_id')->unique()->count() + 
                             $schedules->whereNotNull('backup_driver_id')->pluck('backup_driver_id')->unique()->count();
        $uniqueUnitsCount = $schedules->pluck('unit_id')->unique()->count();
        
        // Build route-unit-driver matrix
        $routeUnitDrivers = $this->buildRouteUnitDriverMatrix($schedules, $dateRange);
        
        return view('modules.admin.schedules.consolidated', [
            'month' => $month,
            'year' => $year,
            'period' => $period,
            'dateRange' => $dateRange,
            'routes' => $routes,
            'drivers' => $drivers,
            'units' => $units,
            'selectedRoute' => $selectedRoute,
            'selectedDriver' => $selectedDriver,
            'selectedUnit' => $selectedUnit,
            'selectedShift' => $selectedShift,
            'routeUnitDrivers' => $routeUnitDrivers,
            'monthName' => Carbon::createFromDate($year, $month, 1)->format('F'),
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $endDate->format('Y-m-d'),
            'totalAssignments' => $totalAssignments,
            'uniqueDriversCount' => $uniqueDriversCount,
            'uniqueUnitsCount' => $uniqueUnitsCount,
            'holidays' => $holidays,
            'unitRenops' => $unitRenops,
        ]);
    }
    
    /**
     * Build the matrix of route-unit-driver combinations
     * 
     * @param \Illuminate\Database\Eloquent\Collection $schedules
     * @param array $dateRange
     * @return array
     */
    private function buildRouteUnitDriverMatrix($schedules, $dateRange)
    {
        $matrix = [];
        
        // Get all units and routes that have schedules in this period
        $unitIds = $schedules->pluck('unit_id')->unique()->toArray();
        $routeIds = $schedules->pluck('route_id')->unique()->toArray();
        
        $units = Unit::whereIn('id', $unitIds)->orderBy('unit_number')->get();
        $routes = Route::whereIn('id', $routeIds)->orderBy('route_number')->get();
        
        // Group schedules by route, unit, driver and shift
        foreach ($routes as $route) {
            $routeMatrix = [
                'route' => $route,
                'units' => []
            ];
            
            $routeSchedules = $schedules->where('route_id', $route->id);
            if ($routeSchedules->isEmpty()) {
                continue;
            }
            
            $routeUnitIds = $routeSchedules->pluck('unit_id')->unique()->toArray();
            $routeUnits = $units->whereIn('id', $routeUnitIds);
            
            foreach ($routeUnits as $unit) {
                $unitMatrix = [
                    'unit' => $unit,
                    'drivers' => []
                ];
                
                $unitSchedules = $routeSchedules->where('unit_id', $unit->id);
                if ($unitSchedules->isEmpty()) {
                    continue;
                }
                
                // Group by driver
                $driverIds = $unitSchedules->pluck('driver_id')->unique();
                
                foreach ($driverIds as $driverId) {
                    $driverSchedules = $unitSchedules->where('driver_id', $driverId);
                    $driver = $driverSchedules->first()->driver;
                    
                    // Initialize driver entry
                    $driverMatrix = [
                        'driver' => $driver,
                        'shifts' => [
                            'pagi' => [
                                'dates' => [],
                                'backup_dates' => []
                            ],
                            'siang' => [
                                'dates' => [],
                                'backup_dates' => []
                            ]
                        ]
                    ];
                    
                    // Collect dates for each shift
                    foreach ($driverSchedules as $schedule) {
                        $date = $schedule->schedule_date->format('Y-m-d');
                        $shift = $schedule->shift;
                        
                        $driverMatrix['shifts'][$shift]['dates'][] = $date;
                    }
                    
                    // Also process backup assignments
                    $backupSchedules = $unitSchedules->where('backup_driver_id', $driverId);
                    foreach ($backupSchedules as $schedule) {
                        $date = $schedule->schedule_date->format('Y-m-d');
                        $shift = $schedule->shift;
                        
                        $driverMatrix['shifts'][$shift]['backup_dates'][] = $date;
                    }
                    
                    $unitMatrix['drivers'][] = $driverMatrix;
                }
                
                $routeMatrix['units'][] = $unitMatrix;
            }
            
            $matrix[] = $routeMatrix;
        }
        
        return $matrix;
    }
    
    /**
     * Export schedules to Excel
     * 
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportExcel(Request $request)
    {
        // Get selected month, year and period from request or use current date
        $month = $request->query('month', Carbon::now()->month);
        $year = $request->query('year', Carbon::now()->year);
        $period = $request->query('period', 1); // Default to first period (1-15)
        $selectedRoute = $request->query('route', null);
        $selectedDriver = $request->query('driver', null);
        $selectedUnit = $request->query('unit', null);
        $selectedShift = $request->query('shift', null);
        
        // Calculate date range for the selected period
        if ($period == 1) {
            $startDate = Carbon::createFromDate($year, $month, 1);
            $endDate = Carbon::createFromDate($year, $month, 15);
        } else {
            $startDate = Carbon::createFromDate($year, $month, 16);
            $endDate = Carbon::createFromDate($year, $month)->endOfMonth();
        }
        
        // Build query for schedules based on filters
        $query = Schedule::with(['driver', 'backupDriver', 'route', 'unit'])
            ->whereBetween('schedule_date', [
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d')
            ]);
            
        // Apply route filter if selected
        if ($selectedRoute) {
            $query->where('route_id', $selectedRoute);
        }
        if ($selectedDriver) {
            $query->where(function($query) use ($selectedDriver) {
                $query->where('driver_id', $selectedDriver)
                      ->orWhere('backup_driver_id', $selectedDriver);
            });
        }
        if ($selectedUnit) {
            $query->where('unit_id', $selectedUnit);
        }
        if ($selectedShift) {
            $query->where('shift', $selectedShift);
        }
            
        // Get schedules
        $schedules = $query->get();
        
        $monthName = Carbon::createFromDate($year, $month, 1)->format('F');
        $filename = "jadwal-{$monthName}-{$year}-periode-{$period}";
        
        return Excel::download(new SchedulesExport($schedules), $filename . '.xlsx');
    }

    /**
     * Export schedules to PDF
     * 
     * @param Request $request
     * @return mixed
     */
    public function exportPdf(Request $request)
    {
        // Get selected month, year and period from request or use current date
        $month = $request->query('month', Carbon::now()->month);
        $year = $request->query('year', Carbon::now()->year);
        $period = $request->query('period', 1); // Default to first period (1-15)
        $selectedRoute = $request->query('route', null);
        $selectedDriver = $request->query('driver', null);
        $selectedUnit = $request->query('unit', null);
        $selectedShift = $request->query('shift', null);
        
        // Calculate date range for the selected period
        if ($period == 1) {
            $startDate = Carbon::createFromDate($year, $month, 1);
            $endDate = Carbon::createFromDate($year, $month, 15);
        } else {
            $startDate = Carbon::createFromDate($year, $month, 16);
            $endDate = Carbon::createFromDate($year, $month)->endOfMonth();
        }
        
        // Build query for schedules based on filters
        $query = Schedule::with(['driver', 'backupDriver', 'route', 'unit'])
            ->whereBetween('schedule_date', [
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d')
            ]);
            
        // Apply route filter if selected
        if ($selectedRoute) {
            $query->where('route_id', $selectedRoute);
        }
        if ($selectedDriver) {
            $query->where(function($query) use ($selectedDriver) {
                $query->where('driver_id', $selectedDriver)
                      ->orWhere('backup_driver_id', $selectedDriver);
            });
        }
        if ($selectedUnit) {
            $query->where('unit_id', $selectedUnit);
        }
        if ($selectedShift) {
            $query->where('shift', $selectedShift);
        }
            
        // Get schedules
        $schedules = $query->get();
        
        $export = new SchedulesPdfExport($schedules);
        return $export->download();
    }

    /**
     * Export schedules to Matrix PDF
     * 
     * @param Request $request
     * @return mixed
     */
    public function exportMatrixPdf(Request $request)
    {
        // Get selected month, year and period from request or use current date
        $month = $request->query('month', Carbon::now()->month);
        $year = $request->query('year', Carbon::now()->year);
        $period = $request->query('period', 1); // Default to first period (1-15)
        $selectedRoute = $request->query('route', null);
        $selectedDriver = $request->query('driver', null);
        $selectedUnit = $request->query('unit', null);
        $selectedShift = $request->query('shift', null);
        
        // Calculate date range for the selected period
        if ($period == 1) {
            $startDate = Carbon::createFromDate($year, $month, 1);
            $endDate = Carbon::createFromDate($year, $month, 15);
        } else {
            $startDate = Carbon::createFromDate($year, $month, 16);
            $endDate = Carbon::createFromDate($year, $month)->endOfMonth();
        }
        
        // Build query for schedules based on filters
        $query = Schedule::with(['driver', 'backupDriver', 'route', 'unit'])
            ->whereBetween('schedule_date', [
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d')
            ]);
        // Apply route filter if selected
        if ($selectedRoute) {
            $query->where('route_id', $selectedRoute);
        }
        if ($selectedDriver) {
            $query->where(function($query) use ($selectedDriver) {
                $query->where('driver_id', $selectedDriver)
                      ->orWhere('backup_driver_id', $selectedDriver);
            });
        }
        if ($selectedUnit) {
            $query->where('unit_id', $selectedUnit);
        }
        if ($selectedShift) {
            $query->where('shift', $selectedShift);
        }
            
        // Get schedules
        $schedules = $query->get();
        
        $export = new SchedulesMatrixPdfExport($schedules);
        return $export->download();
    }

    /**
     * Show the form for generating schedules automatically.
     *
     * @return \Illuminate\View\View
     */
    public function generateForm()
    {
        return view('modules.admin.schedules.generate');
    }

    /**
     * Delete a schedule
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $schedule = Schedule::findOrFail($id);
        $schedule->delete();

        return redirect()->route('schedules.index')->with('success', 'Jadwal berhasil dihapus');
    }

    /**
     * Reset all schedule data (only in local environment)
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function resetAll()
    {
        // Only allow this in local environment
        if (app()->environment('local')) {
            // Delete all records from the schedules table
            Schedule::truncate();
            
            return redirect()->route('schedules.index')
                ->with('success', 'Semua data jadwal berhasil direset');
        }
        
        return redirect()->route('schedules.index')
            ->with('error', 'Reset data jadwal hanya diperbolehkan di lingkungan local');
    }

    /**
     * Generate schedules automatically for a date range.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function generate(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);
        
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        
        // Calculate date difference to ensure it's within limits
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $daysDiff = $start->diffInDays($end) + 1;
        
        if ($daysDiff > 15) {
            return back()->withErrors([
                'date_range' => 'Schedule generation is limited to 15 days per run.'
            ]);
        }
        
        try {
            $result = Schedule::autoGenerate($startDate, $endDate);
            $createdCount = Schedule::whereBetween('schedule_date', [$startDate, $endDate])->count();
            if (!isset($result['success']) || $result['success'] === false) {
                $errorMessage = $result['message'] ?? 'Failed to generate schedules. Please check the logs for details.';
                return back()->withErrors(['generation' => $errorMessage]);
            }
            
            $generationResults = [
                'success' => $result['success'] ?? false,
                'created' => $result['success'] ?? 0,
                'skipped' => $result['failed'] ?? 0,
                'messages' => $result['messages'] ?? [],
                'failed' => $result['failed'] ?? 0,
                'actual_in_db' => $createdCount 
            ];
            
            return redirect()->route('schedules.index')->with('generation_results', $generationResults)
                         ->with('success_message', "Successfully generated {$generationResults['created']} schedules. Actual records in database: {$createdCount}");
        } catch (\Exception $e) {
            Log::error($e->getTraceAsString());
            return back()->withErrors(['generation' => 'An error occurred while generating schedules: ' . $e->getMessage()]);
        }
    }
}
