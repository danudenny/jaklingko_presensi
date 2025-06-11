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
use App\Models\LeaveRequest;
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
    public function index(Request $request)
    {
        $month = $request->query('month', Carbon::now()->month);
        $year = $request->query('year', Carbon::now()->year);
        $period = $request->query('period', 1);
        $selectedRoute = $request->query('route', null);
        $selectedDriverType = $request->query('driver_type', null);
        $selectedDriver = $request->query('driver', null);
        $selectedUnit = $request->query('unit', null);
        $selectedShift = $request->query('shift', null);
        
        if ($period == 1) {
            $startDate = Carbon::createFromDate($year, $month, 1);
            $endDate = Carbon::createFromDate($year, $month, 15);
        } else {
            $startDate = Carbon::createFromDate($year, $month, 16);
            $endDate = Carbon::createFromDate($year, $month)->endOfMonth();
        }
        
        $dateRange = [];
        $currentDate = clone $startDate;
        
        while ($currentDate->lte($endDate)) {
            $dateRange[] = $currentDate->format('Y-m-d');
            $currentDate->addDay();
        }
        
        $holidaysCollection = Holiday::whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get();
            
        $holidays = [];
        foreach ($holidaysCollection as $holiday) {
            $formattedDate = $holiday->date->format('Y-m-d');
            $holidays[$formattedDate] = $holiday->name;
        }
                
        $unitRenopsCollection = UnitRenops::whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get();
            
        $unitRenops = [];
        foreach ($unitRenopsCollection as $renop) {
            $formattedDate = $renop->date->format('Y-m-d');
            if (!isset($unitRenops[$formattedDate])) {
                $unitRenops[$formattedDate] = [];
            }
            $unitRenops[$formattedDate][$renop->unit_id] = true;
        }
        
        $routes = Route::orderBy('route_number')->get();
        $drivers = Driver::where('status', 'aktif')
            ->orderByRaw('CASE WHEN type = "batangan" THEN 0 ELSE 1 END')
            ->orderBy('name')
            ->get();
        $units = Unit::where('status', 'aktif')
            ->orderBy('unit_number')
            ->get();
        
        $query = Schedule::with(['driver', 'backupDriver', 'route', 'unit'])
            ->whereBetween('schedule_date', [
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d')
            ]);
            
        if ($selectedRoute) {
            $query->where('route_id', $selectedRoute);
        }
        
        if ($selectedDriverType) {
            $query->whereHas('driver', function($query) use ($selectedDriverType) {
                $query->where('type', $selectedDriverType);
            });
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
            
        $schedules = $query->get();
        
        // Filter schedules to only include those with status = 'scheduled'
        $scheduledSchedules = $schedules->where('status', 'scheduled');
        
        $totalAssignments = $scheduledSchedules->count();
        $uniqueDriversCount = $scheduledSchedules->pluck('driver_id')->unique()->count() + 
                             $scheduledSchedules->whereNotNull('backup_driver_id')->pluck('backup_driver_id')->unique()->count();
        $uniqueUnitsCount = $scheduledSchedules->pluck('unit_id')->unique()->count();
        
        $leaveRequests = LeaveRequest::with('driver')
            ->where('status', 'approved')
            ->where(function($query) use ($startDate, $endDate) {
                $query->where(function($q) use ($startDate, $endDate) {
                    $q->where('start_date', '<=', $endDate->format('Y-m-d'))
                      ->where('end_date', '>=', $startDate->format('Y-m-d'));
                });
            })
            ->get();
            
        $driversOnLeave = [];
        foreach ($leaveRequests as $leaveRequest) {
            $leaveStart = Carbon::parse($leaveRequest->start_date);
            $leaveEnd = Carbon::parse($leaveRequest->end_date);
            $leavePeriod = CarbonPeriod::create($leaveStart, $leaveEnd);
            
            foreach ($leavePeriod as $date) {
                $dateStr = $date->format('Y-m-d');
                if (!isset($driversOnLeave[$dateStr])) {
                    $driversOnLeave[$dateStr] = [];
                }
                $driversOnLeave[$dateStr][$leaveRequest->driver_id] = true;
            }
        }
        
        $routeUnitDrivers = $this->buildRouteUnitDriverMatrix($schedules, $dateRange);
        
        $assignedDriverIds = $schedules->pluck('driver_id')->unique()->toArray();
        $assignedBackupDriverIds = $schedules->whereNotNull('backup_driver_id')->pluck('backup_driver_id')->unique()->toArray();
        $allAssignedDriverIds = array_unique(array_merge($assignedDriverIds, $assignedBackupDriverIds));
        
        $unassignedDrivers = Driver::where('status', 'aktif')
            ->whereNotIn('id', $allAssignedDriverIds)
            ->get();
            
        $unassignedBatanganDrivers = $unassignedDrivers->where('type', 'batangan');
        $unassignedCadanganDrivers = $unassignedDrivers->where('type', 'cadangan');
        
        foreach ($unassignedDrivers as $driver) {
            $monthlySchedules = Schedule::where('driver_id', $driver->id)
                ->whereYear('schedule_date', $year)
                ->whereMonth('schedule_date', $month)
                ->get();
                
            $driver->total_schedules = $monthlySchedules->count();
            $driver->total_morning = $monthlySchedules->where('shift', 'pagi')->count();
            $driver->total_afternoon = $monthlySchedules->where('shift', 'siang')->count();
        }

        // We don't need this mapping anymore since we're handling backup drivers in the buildRouteUnitDriverMatrix method
        
        return view('modules.admin.schedules.consolidated', [
            'month' => $month,
            'year' => $year,
            'period' => $period,
            'dateRange' => $dateRange,
            'routes' => $routes,
            'drivers' => $drivers,
            'units' => $units,
            'selectedRoute' => $selectedRoute,
            'selectedDriverType' => $selectedDriverType,
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
            'driversOnLeave' => $driversOnLeave,
            'unassignedBatanganDrivers' => $unassignedBatanganDrivers,
            'unassignedCadanganDrivers' => $unassignedCadanganDrivers,
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
        
        // Get all active drivers for reference
        $allActiveDrivers = Driver::where('status', 'aktif')->get();
        
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
                    // Even if there are no schedules, we still want to include all drivers assigned to this unit
                    $unitDrivers = $allActiveDrivers->filter(function($driver) use ($unit) {
                        return $driver->units->contains('id', $unit->id);
                    });
                    
                    foreach ($unitDrivers as $driver) {
                        $driverMatrix = [
                            'driver' => $driver,
                            'shifts' => [
                                'pagi' => [
                                    'dates' => [],
                                    'backup_dates' => [],
                                    'maintenance_dates' => []
                                ],
                                'siang' => [
                                    'dates' => [],
                                    'backup_dates' => [],
                                    'maintenance_dates' => []
                                ]
                            ]
                        ];
                        $unitMatrix['drivers'][] = $driverMatrix;
                    }
                    
                    $routeMatrix['units'][] = $unitMatrix;
                    continue;
                }
                
                // Get all drivers assigned to this unit
                $unitDrivers = $allActiveDrivers->filter(function($driver) use ($unit) {
                    return $driver->units->contains('id', $unit->id);
                });
                
                // Get all driver IDs with schedules for this unit
                $driverIdsWithSchedules = $unitSchedules->pluck('driver_id')->unique()->toArray();
                $backupDriverIdsWithSchedules = $unitSchedules->whereNotNull('backup_driver_id')
                    ->pluck('backup_driver_id')->unique()->toArray();
                
                // Process drivers with schedules first
                foreach ($driverIdsWithSchedules as $driverId) {
                    $driverSchedules = $unitSchedules->where('driver_id', $driverId);
                    $driver = $driverSchedules->first()->driver;
                    
                    // Initialize driver entry
                    $driverMatrix = [
                        'driver' => $driver,
                        'shifts' => [
                            'pagi' => [
                                'dates' => [],
                                'backup_dates' => [],
                                'maintenance_dates' => []
                            ],
                            'siang' => [
                                'dates' => [],
                                'backup_dates' => [],
                                'maintenance_dates' => []
                            ]
                        ]
                    ];
                    
                    // Collect dates for each shift
                    foreach ($driverSchedules as $schedule) {
                        $date = $schedule->schedule_date->format('Y-m-d');
                        $shift = $schedule->shift;
                        
                        // Check if the schedule is in maintenance status
                        if ($schedule->status === 'maintenance') {
                            $driverMatrix['shifts'][$shift]['maintenance_dates'][] = $date;
                        } else {
                            $driverMatrix['shifts'][$shift]['dates'][] = $date;
                        }
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
                
                // Process backup drivers who have backup assignments
                foreach ($backupDriverIdsWithSchedules as $backupDriverId) {
                    // Skip if already processed as a regular driver
                    if (in_array($backupDriverId, $driverIdsWithSchedules)) {
                        continue;
                    }
                    
                    $backupDriver = $allActiveDrivers->firstWhere('id', $backupDriverId);
                    if (!$backupDriver) continue;
                    
                    $backupSchedules = $unitSchedules->where('backup_driver_id', $backupDriverId);
                    
                    $driverMatrix = [
                        'driver' => $backupDriver,
                        'shifts' => [
                            'pagi' => [
                                'dates' => [],
                                'backup_dates' => [],
                                'maintenance_dates' => []
                            ],
                            'siang' => [
                                'dates' => [],
                                'backup_dates' => [],
                                'maintenance_dates' => []
                            ]
                        ]
                    ];
                    
                    foreach ($backupSchedules as $schedule) {
                        $date = $schedule->schedule_date->format('Y-m-d');
                        $shift = $schedule->shift;
                        $driverMatrix['shifts'][$shift]['backup_dates'][] = $date;
                    }
                    
                    $unitMatrix['drivers'][] = $driverMatrix;
                }
                
                // Add drivers assigned to this unit but without any schedules
                foreach ($unitDrivers as $driver) {
                    // Skip if already processed
                    if (in_array($driver->id, $driverIdsWithSchedules) || in_array($driver->id, $backupDriverIdsWithSchedules)) {
                        continue;
                    }
                    
                    $driverMatrix = [
                        'driver' => $driver,
                        'shifts' => [
                            'pagi' => [
                                'dates' => [],
                                'backup_dates' => [],
                                'maintenance_dates' => []
                            ],
                            'siang' => [
                                'dates' => [],
                                'backup_dates' => [],
                                'maintenance_dates' => []
                            ]
                        ]
                    ];
                    
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
        $selectedDriverType = $request->query('driver_type', null);
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
            
        // Get schedules - only include those with status = 'scheduled'
        $schedules = $query->where('status', 'scheduled')->get();
        
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
        $selectedDriverType = $request->query('driver_type', null);
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
            
        // Get schedules - only include those with status = 'scheduled'
        $schedules = $query->where('status', 'scheduled')->get();
        
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
        
        // Pass the correct parameters to the SchedulesMatrixPdfExport constructor: month, year, period
        $export = new SchedulesMatrixPdfExport($month, $year, $period);
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

    public function update(Request $request): JsonResponse
    {
        try {            
            $unitId = $request->input('unitId');
            $additions = $request->input('additions', []);
            $removals = $request->input('removals', []);
            $month = $request->input('month');
            $year = $request->input('year');
            $period = $request->input('period');
            
            if (empty($unitId) || (empty($additions) && empty($removals)) || empty($month) || empty($year) || empty($period)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Missing required fields'
                ], 400);
            }
            
            DB::beginTransaction();
            
            $unit = Unit::findOrFail($unitId);
            $route = $unit->routes->first();
            
            if (!$route) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unit tidak memiliki rute yang terkait'
                ], 400);
            }
            
            if ($period == 1) {
                $startDate = Carbon::createFromDate($year, $month, 1);
                $endDate = Carbon::createFromDate($year, $month, 15);
            } else {
                $startDate = Carbon::createFromDate($year, $month, 16);
                $endDate = Carbon::createFromDate($year, $month)->endOfMonth();
            }
            
            $additionDates = [];
            $additionShifts = [];
            $additionDrivers = [];
            $removalDates = [];
            $removalShifts = [];
            
            foreach ($additions as $addition) {
                if (!empty($addition['date']) && !empty($addition['shift'])) {
                    $date = $addition['date'];
                    
                    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                        try {
                            if (preg_match('/^(\d{1,2})/', $date, $matches)) {
                                $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                                $formattedDate = Carbon::createFromDate($year, $month, $day)->format('Y-m-d');
                                $date = $formattedDate;                                
                            }
                        } catch (\Exception $e) {
                            continue;
                        }
                    }
                    
                    $additionDates[] = $date;
                    $additionShifts[$date] = $addition['shift'];
                    $additionDrivers[$date] = $addition['driverId'] ?? null;
                }
            }
            
            foreach ($removals as $removal) {
                if (!empty($removal['date']) && !empty($removal['shift'])) {
                    $date = $removal['date'];
                    
                    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                        try {
                            if (preg_match('/^(\d{1,2})/', $date, $matches)) {
                                $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                                $formattedDate = Carbon::createFromDate($year, $month, $day)->format('Y-m-d');
                                $date = $formattedDate;                                
                            }
                        } catch (\Exception $e) {
                            continue;
                        }
                    }
                    
                    $removalDates[] = $date;
                    $removalShifts[$date] = $removal['shift'];
                    $removalDrivers[$date] = $removal['driverId'] ?? null;
                }
            }
            
            $deleted = 0;
            if (!empty($removalDates)) {
                $query = Schedule::where('unit_id', $unitId);
                
                $query->where(function($q) use ($removalDates, $removalShifts, $removalDrivers) {
                    foreach ($removalDates as $date) {
                        $shift = $removalShifts[$date] ?? null;
                        $driverId = $removalDrivers[$date] ?? null;
                        if ($shift) {
                            $q->orWhere(function($subq) use ($date, $shift, $driverId) {
                                $subq->where('schedule_date', $date)
                                    ->where('shift', $shift);
                                
                                // If driver ID is provided, use it to make the deletion more specific
                                if ($driverId) {
                                    $subq->where('driver_id', $driverId);
                                }
                            });
                        }
                    }
                });
                
                $sql = $query->toSql();
                $bindings = $query->getBindings();
                
                $deleted = $query->delete();
            }
            
            $deletedForAdditions = 0;
            if (!empty($additionDates)) {
                $query = Schedule::where('unit_id', $unitId);
                
                $query->where(function($q) use ($additionDates, $additionShifts, $additionDrivers) {
                    foreach ($additionDates as $date) {
                        $shift = $additionShifts[$date] ?? null;
                        $driverId = $additionDrivers[$date] ?? null;
                        if ($shift) {
                            $q->orWhere(function($subq) use ($date, $shift, $driverId) {
                                $subq->where('schedule_date', $date)
                                    ->where('shift', $shift);
                                    
                                // If driver ID is provided, use it to make the deletion more specific
                                if ($driverId) {
                                    $subq->where('driver_id', $driverId);
                                }
                            });
                        }
                    }
                });
                
                $sql = $query->toSql();
                $bindings = $query->getBindings();
                
                $deletedForAdditions = $query->delete();
            }
            
            $created = 0;
            foreach ($additions as $addition) {
                if (!empty($addition['date']) && !empty($addition['shift']) && !empty($addition['driverId'])) {
                    $date = $addition['date'];
                    
                    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                        try {
                            if (preg_match('/^(\d{1,2})/', $date, $matches)) {
                                $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                                $formattedDate = Carbon::createFromDate($year, $month, $day)->format('Y-m-d');
                                $date = $formattedDate;
                            }
                        } catch (\Exception $e) {
                            continue;
                        }
                    }
                    
                    Schedule::create([
                        'unit_id' => $unitId,
                        'route_id' => $route->id,
                        'driver_id' => $addition['driverId'],
                        'schedule_date' => $date,
                        'shift' => $addition['shift'],
                        'status' => 'scheduled',
                    ]);
                    
                    $created++;
                }
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => "Jadwal berhasil diperbarui. Menambahkan {$created} jadwal baru dan menghapus " . ($deleted + $deletedForAdditions) . " jadwal yang ada.",
                'data' => [
                    'created' => $created,
                    'deleted' => $deleted + $deletedForAdditions
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memperbarui jadwal: ' . $e->getMessage()
            ], 500);
        }
    }
}
