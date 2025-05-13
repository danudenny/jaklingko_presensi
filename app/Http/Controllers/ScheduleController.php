<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\Driver;
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
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $driverId = $request->input('driver_id');
        $unitId = $request->input('unit_id');
        $routeId = $request->input('route_id');
        $perPage = $request->input('per_page', 15); // Default to 15 items per page

        // Default to today if no dates provided
        if (!$startDate && !$endDate) {
            $startDate = Carbon::today()->format('Y-m-d');
            $endDate = Carbon::today()->format('Y-m-d');
        }

        $query = Schedule::with(['driver', 'backupDriver', 'route', 'unit'])
            ->whereBetween('schedule_date', [$startDate, $endDate]);

        if (!empty($driverId)) {
            $query->where('driver_id', $driverId);
        }

        if (!empty($unitId)) {
            $query->where('unit_id', $unitId);
        }

        if (!empty($routeId)) {
            $query->where('route_id', $routeId);
        }

        // Get all schedules for counting by shift (needed for the summary)
        $allSchedules = (clone $query)->get();

        // Get paginated results
        $schedules = $query->orderBy('schedule_date')
                          ->orderBy('shift')
                          ->paginate($perPage)
                          ->withQueryString(); // Preserve query parameters in pagination links

        $drivers = Driver::active()->orderBy('name')->get();
        $units = Unit::active()->orderBy('unit_number')->get();
        $routes = Route::active()->orderBy('route_number')->get();

        // Calculate shift counts for the summary
        $morningCount = $allSchedules->where('shift', 'pagi')->count() + $allSchedules->where('shift', 'morning')->count();
        $eveningCount = $allSchedules->where('shift', 'siang')->count() + $allSchedules->where('shift', 'evening')->count();

        // Calculate driver type counts
        $batanganCount = $allSchedules->filter(function($schedule) {
            return $schedule->driver->type == 'batangan';
        })->count();

        $cadanganCount = $allSchedules->filter(function($schedule) {
            return $schedule->driver->type == 'cadangan';
        })->count();

        // Get selected driver, unit, and route for filter display
        $selectedDriver = null;
        $selectedUnit = null;
        $selectedRoute = null;

        if (!empty($driverId)) {
            $selectedDriver = Driver::find($driverId);
        }

        if (!empty($unitId)) {
            $selectedUnit = Unit::find($unitId);
        }

        if (!empty($routeId)) {
            $selectedRoute = Route::find($routeId);
        }

        return view('modules.admin.schedules.index', compact(
            'schedules',
            'startDate',
            'endDate',
            'drivers',
            'driverId',
            'units',
            'unitId',
            'routes',
            'routeId',
            'morningCount',
            'eveningCount',
            'batanganCount',
            'cadanganCount',
            'allSchedules',
            'selectedDriver',
            'selectedUnit',
            'selectedRoute'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $drivers = Driver::active()->get();
        $routes = Route::active()->get();
        $units = Unit::active()->get();

        return view('modules.admin.schedules.create', compact('drivers', 'routes', 'units'));
    }

    /**
     * Get units for a specific route (for chained filtering)
     */
    public function getUnitsByRoute($routeId)
    {
        // First try to get units from the relationship table
        $route = Route::findOrFail($routeId);
        $unitIds = $route->units()->pluck('id')->toArray();

        // If no relationships exist, get units from schedules
        if (empty($unitIds)) {
            $unitIds = Schedule::where('route_id', $routeId)
                ->distinct()
                ->pluck('unit_id')
                ->toArray();
        }

        return response()->json($unitIds);
    }

    /**
     * Get drivers qualified for a specific unit (for chained filtering)
     */
    public function getDriversByUnit($unitId)
    {
        // First try to get drivers from the relationship table
        $unit = Unit::findOrFail($unitId);
        $driverIds = $unit->drivers()->pluck('id')->toArray();

        // If no relationships exist, get drivers from schedules
        if (empty($driverIds)) {
            $driverIds = Schedule::where('unit_id', $unitId)
                ->distinct()
                ->pluck('driver_id')
                ->toArray();
        }

        return response()->json($driverIds);
    }

    /**
     * Get available drivers for a specific date, shift, unit, and route
     */
    public function getAvailableDrivers(Request $request)
    {
        $date = $request->query('date');
        $shift = $request->query('shift');
        $unitId = $request->query('unit_id');
        $routeId = $request->query('route_id');

        // Validate input
        if (!$date || !$shift || !$unitId || !$routeId) {
            return response()->json(['error' => 'Missing required parameters'], 400);
        }

        // Get all active drivers
        $drivers = Driver::active()->get();

        // Get the unit and route
        $unit = Unit::find($unitId);
        $route = Route::find($routeId);

        if (!$unit || !$route) {
            return response()->json(['error' => 'Invalid unit or route'], 400);
        }

        // Get drivers already assigned to this date and shift
        $assignedDriverIds = Schedule::where('schedule_date', $date)
            ->where('shift', $shift)
            ->pluck('driver_id')
            ->toArray();

        // Get drivers who had evening shift yesterday (if checking for morning shift)
        $yesterdayEveningDriverIds = [];
        if ($shift === 'morning') {
            $yesterday = date('Y-m-d', strtotime($date . ' -1 day'));
            $yesterdayEveningDriverIds = Schedule::where('schedule_date', $yesterday)
                ->where('shift', 'evening')
                ->where('status', '!=', 'on_leave')
                ->pluck('driver_id')
                ->toArray();
        }

        // Get drivers on leave for this date
        $onLeaveDriverIds = Schedule::where('schedule_date', $date)
            ->where('status', 'on_leave')
            ->pluck('driver_id')
            ->toArray();

        // Filter available drivers
        $availableDriverIds = $drivers->filter(function ($driver) use ($unit, $route, $assignedDriverIds, $yesterdayEveningDriverIds, $onLeaveDriverIds) {
            // Check if driver is qualified for this unit and route
            $isQualifiedForUnit = $driver->units->contains($unit->id);
            $isQualifiedForRoute = $driver->routes->contains($route->id);

            // Check if driver is already assigned or on leave
            $isAvailable = !in_array($driver->id, $assignedDriverIds) &&
                          !in_array($driver->id, $onLeaveDriverIds);

            // Check shift sequence constraint
            $canWorkShift = !in_array($driver->id, $yesterdayEveningDriverIds);

            return $isQualifiedForUnit && $isQualifiedForRoute && $isAvailable && $canWorkShift;
        })->pluck('id')->toArray();

        return response()->json([
            'available_drivers' => $availableDriverIds,
            'total_available' => count($availableDriverIds)
        ]);
    }

    /**
     * Check if a driver has a shift on a specific date
     */
    public function checkDriverSchedule(Request $request, $driverId)
    {
        $date = $request->query('date');
        $shift = $request->query('shift');

        $hasShift = Schedule::where('driver_id', $driverId)
            ->where('schedule_date', $date)
            ->where('shift', $shift)
            ->where('status', '!=', 'on_leave')
            ->exists();

        return response()->json(['has_' . $shift . '_shift' => $hasShift]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'driver_id' => 'required|exists:drivers,id',
            'route_id' => 'required|exists:routes,id',
            'unit_id' => 'required|exists:units,id',
            'schedule_date' => 'required|date',
            'shift' => 'required|in:morning,evening',
            'status' => 'required|in:scheduled,completed,absent,on_leave',
            'backup_driver_id' => 'nullable|exists:drivers,id',
            'notes' => 'nullable|string',
        ]);

        // Check if the driver is qualified for this route and unit
        $driver = Driver::findOrFail($validated['driver_id']);
        $route = Route::findOrFail($validated['route_id']);
        $unit = Unit::findOrFail($validated['unit_id']);

        // Validate driver qualifications
        if (!$driver->routes->contains($route->id)) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['driver_id' => 'The selected driver is not qualified for this route.']);
        }

        if (!$driver->units->contains($unit->id)) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['driver_id' => 'The selected driver is not qualified to drive this unit.']);
        }

        // Check shift sequence constraint
        if ($validated['shift'] === 'morning') {
            $previousDayEvening = Schedule::where('driver_id', $driver->id)
                ->where('schedule_date', Carbon::parse($validated['schedule_date'])->subDay()->format('Y-m-d'))
                ->where('shift', 'evening')
                ->where('status', '!=', 'on_leave')
                ->exists();

            if ($previousDayEvening) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['driver_id' => 'This driver cannot be assigned to a morning shift after an evening shift.']);
            }
        }

        // Check if driver is already assigned to this shift on this date
        $existingSchedule = Schedule::where('driver_id', $driver->id)
            ->where('schedule_date', $validated['schedule_date'])
            ->where('shift', $validated['shift'])
            ->exists();

        if ($existingSchedule) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['driver_id' => 'This driver is already assigned to this shift on this date.']);
        }

        // If driver type is non-fixed, check if there are available fixed drivers
        if ($driver->type === 'non-fixed') {
            $availableFixedDrivers = Driver::fixed()
                ->active()
                ->whereHas('routes', function ($query) use ($route) {
                    $query->where('routes.id', $route->id);
                })
                ->whereHas('units', function ($query) use ($unit) {
                    $query->where('units.id', $unit->id);
                })
                ->whereDoesntHave('schedules', function ($query) use ($validated) {
                    $query->where('schedule_date', $validated['schedule_date'])
                        ->where('shift', $validated['shift']);
                })
                ->get()
                ->filter(function ($fixedDriver) use ($validated) {
                    return $fixedDriver->isAvailableFor($validated['schedule_date'], $validated['shift']);
                });

            if ($availableFixedDrivers->isNotEmpty()) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['driver_id' => 'There are available fixed drivers for this shift. Please assign a fixed driver first.']);
            }
        }

        Schedule::create($validated);

        return redirect()->route('schedules.index', ['start_date' => $validated['schedule_date'], 'end_date' => $validated['schedule_date']])
            ->with('success', 'Schedule created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Schedule $schedule)
    {
        $schedule->load(['driver', 'backupDriver', 'route', 'unit']);
        return view('modules.admin.schedules.show', compact('schedule'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Schedule $schedule)
    {
        $drivers = Driver::active()->get();
        $routes = Route::active()->get();
        $units = Unit::active()->get();
        $backupDrivers = $schedule->findAvailableBackupDrivers();

        return view('modules.admin.schedules.edit', compact('schedule', 'drivers', 'routes', 'units', 'backupDrivers'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Schedule $schedule)
    {
        $validated = $request->validate([
            'driver_id' => 'required|exists:drivers,id',
            'route_id' => 'required|exists:routes,id',
            'unit_id' => 'required|exists:units,id',
            'schedule_date' => 'required|date',
            'shift' => 'required|in:morning,evening',
            'status' => 'required|in:scheduled,completed,absent,on_leave',
            'backup_driver_id' => 'nullable|exists:drivers,id',
            'notes' => 'nullable|string',
        ]);

        // Only perform validation if driver, date, or shift has changed
        if ($schedule->driver_id != $validated['driver_id'] ||
            $schedule->schedule_date != $validated['schedule_date'] ||
            $schedule->shift != $validated['shift']) {

            // Check if the driver is qualified for this route and unit
            $driver = Driver::findOrFail($validated['driver_id']);
            $route = Route::findOrFail($validated['route_id']);
            $unit = Unit::findOrFail($validated['unit_id']);

            // Validate driver qualifications
            if (!$driver->routes->contains($route->id)) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['driver_id' => 'The selected driver is not qualified for this route.']);
            }

            if (!$driver->units->contains($unit->id)) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['driver_id' => 'The selected driver is not qualified to drive this unit.']);
            }

            // Check shift sequence constraint
            if ($validated['shift'] === 'morning') {
                $previousDayEvening = Schedule::where('driver_id', $driver->id)
                    ->where('schedule_date', Carbon::parse($validated['schedule_date'])->subDay()->format('Y-m-d'))
                    ->where('shift', 'evening')
                    ->where('status', '!=', 'on_leave')
                    ->where('id', '!=', $schedule->id)
                    ->exists();

                if ($previousDayEvening) {
                    return redirect()->back()
                        ->withInput()
                        ->withErrors(['driver_id' => 'This driver cannot be assigned to a morning shift after an evening shift.']);
                }
            }

            // Check if driver is already assigned to this shift on this date
            $existingSchedule = Schedule::where('driver_id', $driver->id)
                ->where('schedule_date', $validated['schedule_date'])
                ->where('shift', $validated['shift'])
                ->where('id', '!=', $schedule->id)
                ->exists();

            if ($existingSchedule) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['driver_id' => 'This driver is already assigned to this shift on this date.']);
            }

            // If driver type is non-fixed, check if there are available fixed drivers
            if ($driver->type === 'non-fixed') {
                $availableFixedDrivers = Driver::fixed()
                    ->active()
                    ->whereHas('routes', function ($query) use ($route) {
                        $query->where('routes.id', $route->id);
                    })
                    ->whereHas('units', function ($query) use ($unit) {
                        $query->where('units.id', $unit->id);
                    })
                    ->whereDoesntHave('schedules', function ($query) use ($validated, $schedule) {
                        $query->where('schedule_date', $validated['schedule_date'])
                            ->where('shift', $validated['shift'])
                            ->where('id', '!=', $schedule->id);
                    })
                    ->get()
                    ->filter(function ($fixedDriver) use ($validated) {
                        return $fixedDriver->isAvailableFor($validated['schedule_date'], $validated['shift']);
                    });

                if ($availableFixedDrivers->isNotEmpty()) {
                    return redirect()->back()
                        ->withInput()
                        ->withErrors(['driver_id' => 'There are available fixed drivers for this shift. Please assign a fixed driver first.']);
                }
            }
        }

        $schedule->update($validated);

        return redirect()->route('schedules.index', ['start_date' => $validated['schedule_date'], 'end_date' => $validated['schedule_date']])
            ->with('success', 'Schedule updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Schedule $schedule)
    {
        $date = $schedule->schedule_date;
        $schedule->delete();

        return redirect()->route('schedules.index', ['start_date' => $date, 'end_date' => $date])
            ->with('success', 'Schedule deleted successfully.');
    }

    /**
     * Mark a driver as unavailable and find backup drivers.
     */
    public function markUnavailable(Schedule $schedule)
    {
        $backupDrivers = $schedule->findAvailableBackupDrivers();

        return view('schedules.unavailable', compact('schedule', 'backupDrivers'));
    }

    /**
     * Assign a backup driver to a schedule.
     */
    public function assignBackup(Request $request, Schedule $schedule)
    {
        $validated = $request->validate([
            'backup_driver_id' => 'required|exists:drivers,id',
            'notes' => 'nullable|string',
        ]);

        $schedule->update([
            'backup_driver_id' => $validated['backup_driver_id'],
            'status' => 'on_leave',
            'notes' => $validated['notes'],
        ]);

        return redirect()->route('schedules.index', ['start_date' => $schedule->schedule_date, 'end_date' => $schedule->schedule_date])
            ->with('success', 'Backup driver assigned successfully.');
    }

    /**
     * Generate a weekly schedule view.
     */
    public function weekly(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfWeek()->format('Y-m-d'));
        $endDate = Carbon::parse($startDate)->addDays(6)->format('Y-m-d');

        $query = Schedule::with(['driver', 'backupDriver', 'route', 'unit'])
            ->whereBetween('schedule_date', [$startDate, $endDate]);

        // Apply filters if provided
        if ($request->filled('driver_type')) {
            $query->whereHas('driver', function ($q) use ($request) {
                $q->where('type', $request->input('driver_type'));
            });
        }

        if ($request->filled('route_id')) {
            $query->where('route_id', $request->input('route_id'));
        }

        if ($request->filled('unit_id')) {
            $query->where('unit_id', $request->input('unit_id'));
        }

        $schedules = $query->orderBy('schedule_date')
            ->orderBy('shift')
            ->get()
            ->groupBy('schedule_date');

        return view('modules.admin.schedules.weekly', compact('schedules', 'startDate', 'endDate'));
    }

    /**
     * Display schedules in a calendar view.
     */
    public function calendar(Request $request)
    {
        $month = $request->input('month', Carbon::now()->month);
        $year = $request->input('year', Carbon::now()->year);

        $date = Carbon::createFromDate($year, $month, 1);
        $previousMonth = $date->copy()->subMonth();
        $nextMonth = $date->copy()->addMonth();

        // Get all schedules for the month
        $startDate = $date->copy()->startOfMonth()->format('Y-m-d');
        $endDate = $date->copy()->endOfMonth()->format('Y-m-d');

        $query = Schedule::with(['driver', 'backupDriver', 'route', 'unit'])
            ->whereBetween('schedule_date', [$startDate, $endDate]);

        // Apply filters if provided
        if ($request->filled('driver_type')) {
            $query->whereHas('driver', function ($q) use ($request) {
                $q->where('type', $request->input('driver_type'));
            });
        }

        if ($request->filled('route_id')) {
            $query->where('route_id', $request->input('route_id'));
        }

        if ($request->filled('unit_id')) {
            $query->where('unit_id', $request->input('unit_id'));
        }

        $schedules = $query->orderBy('schedule_date')
            ->orderBy('shift')
            ->get()
            ->groupBy('schedule_date');

        // Get dates with schedules for highlighting in calendar
        $datesWithSchedules = $schedules->keys()->toArray();

        return view('modules.admin.schedules.calendar', compact('schedules', 'date', 'previousMonth', 'nextMonth', 'datesWithSchedules'));
    }

    /**
     * Get schedules for a specific date (AJAX)
     */
    public function getSchedulesByDate(Request $request, $date)
    {
        $schedules = Schedule::with(['driver', 'backupDriver', 'route', 'unit'])
            ->where('schedule_date', $date)
            ->orderBy('shift')
            ->get();

        return response()->json([
            'date' => $date,
            'formatted_date' => Carbon::parse($date)->locale('id')->isoFormat('dddd, D MMMM Y'),
            'schedules' => $schedules,
            'morning_count' => $schedules->where('shift', 'pagi')->count(),
            'evening_count' => $schedules->where('shift', 'siang')->count(),
            'total_count' => $schedules->count()
        ]);
    }

    /**
     * Show the form for auto-generating schedules.
     */
    public function showGenerateForm()
    {
        return view('modules.admin.schedules.generate');
    }

    /**
     * Auto-generate schedules for a date range.
     */
    public function generateSchedules(Request $request, ScheduleGeneratorService $scheduleGenerator)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $results = $scheduleGenerator->generateSchedules(
            $validated['start_date'],
            $validated['end_date']
        );

        return redirect()->route('schedules.index')
            ->with('success', "Generated {$results['success']} schedules successfully. {$results['failed']} failed.")
            ->with('generation_results', $results);
    }

    /**
     * Export schedules to Excel
     */
    public function exportExcel(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $driverIds = $request->input('driver_id', []);
        $unitIds = $request->input('unit_id', []);

        // Convert single values to arrays for consistent handling
        if (!is_array($driverIds) && !empty($driverIds)) {
            $driverIds = [$driverIds];
        }

        if (!is_array($unitIds) && !empty($unitIds)) {
            $unitIds = [$unitIds];
        }

        // Default to today if no dates provided
        if (!$startDate && !$endDate) {
            $startDate = Carbon::today()->format('Y-m-d');
            $endDate = Carbon::today()->format('Y-m-d');
        }

        $query = Schedule::with(['driver', 'backupDriver', 'route', 'unit'])
            ->whereBetween('schedule_date', [$startDate, $endDate]);

        if (!empty($driverIds)) {
            $query->whereIn('driver_id', $driverIds);
        }

        if (!empty($unitIds)) {
            $query->whereIn('unit_id', $unitIds);
        }

        $schedules = $query->orderBy('schedule_date')
                          ->orderBy('shift')
                          ->get();

        $filename = 'jadwal-' . $startDate . '-' . $endDate . '.xlsx';

        return Excel::download(new SchedulesExport($schedules), $filename);
    }

    /**
     * Export schedules to PDF
     */
    public function exportPdf(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $driverIds = $request->input('driver_id', []);
        $unitIds = $request->input('unit_id', []);

        // Convert single values to arrays for consistent handling
        if (!is_array($driverIds) && !empty($driverIds)) {
            $driverIds = [$driverIds];
        }

        if (!is_array($unitIds) && !empty($unitIds)) {
            $unitIds = [$unitIds];
        }

        // Default to today if no dates provided
        if (!$startDate && !$endDate) {
            $startDate = Carbon::today()->format('Y-m-d');
            $endDate = Carbon::today()->format('Y-m-d');
        }

        $query = Schedule::with(['driver', 'backupDriver', 'route', 'unit'])
            ->whereBetween('schedule_date', [$startDate, $endDate]);

        if (!empty($driverIds)) {
            $query->whereIn('driver_id', $driverIds);
        }

        if (!empty($unitIds)) {
            $query->whereIn('unit_id', $unitIds);
        }

        $schedules = $query->orderBy('schedule_date')
                          ->orderBy('shift')
                          ->get();

        // Get selected drivers and units for filter display
        $selectedDrivers = [];
        $selectedUnits = [];

        if (!empty($driverIds)) {
            $selectedDrivers = Driver::whereIn('id', $driverIds)->pluck('name')->toArray();
        }

        if (!empty($unitIds)) {
            $selectedUnits = Unit::whereIn('id', $unitIds)->pluck('unit_number')->toArray();
        }

        $filters = [
            'drivers' => $selectedDrivers,
            'units' => $selectedUnits,
        ];

        $pdfExport = new SchedulesPdfExport($schedules, $startDate, $endDate, $filters);
        return $pdfExport->download();
    }

    /**
     * Export schedules to Matrix PDF for a period
     */
    public function exportMatrixPdf(Request $request)
    {
        $month = $request->input('month');
        $year = $request->input('year');
        $period = $request->input('period', 1);

        // Default to current month and year if not provided
        if (!$month || !$year) {
            $now = Carbon::now();
            $month = $month ?: $now->month;
            $year = $year ?: $now->year;
        }

        $pdfExport = new SchedulesMatrixPdfExport($month, $year, $period);
        return $pdfExport->download();
    }

    /**
     * Get data for the matrix view
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMatrixData(Request $request)
    {
        $month = $request->input('month', Carbon::now()->month);
        $year = $request->input('year', Carbon::now()->year);

        // Log the received parameters for debugging
        \Log::info('Matrix Data Request', [
            'month' => $month,
            'year' => $year,
            'request_all' => $request->all()
        ]);

        // Calculate the start and end dates for period 1 and 2
        $firstDayOfMonth = Carbon::createFromDate($year, $month, 1);
        $lastDayOfMonth = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        // Period 1: Days 1-15
        $period1StartDate = $firstDayOfMonth->format('Y-m-d');
        $period1EndDate = Carbon::createFromDate($year, $month, 15)->format('Y-m-d');

        // Period 2: Days 16-end of month
        $period2StartDate = Carbon::createFromDate($year, $month, 16)->format('Y-m-d');
        $period2EndDate = $lastDayOfMonth->format('Y-m-d');

        // Get all units with their routes and qualified drivers
        $units = Unit::with(['routes'])->active()->orderBy('unit_number')->get();

        // Get all active drivers
        $drivers = Driver::active()->orderBy('name')->get();

        // Get all schedules for both periods with optimized data loading including backup driver info
        $period1Schedules = Schedule::select('id', 'driver_id', 'unit_id', 'route_id', 'schedule_date', 'shift', 'backup_driver_id', 'status')
            ->with([
                'driver:id,name,type,status',
                'backupDriver:id,name,type,status',
                'unit:id,unit_number,plate_number',
                'route:id,route_number,name,status'
            ])
            ->whereBetween('schedule_date', [$period1StartDate, $period1EndDate])
            ->get()
            ->groupBy(['unit_id', 'shift', 'driver_id']);

        $period2Schedules = Schedule::select('id', 'driver_id', 'unit_id', 'route_id', 'schedule_date', 'shift', 'backup_driver_id', 'status')
            ->with([
                'driver:id,name,type,status',
                'backupDriver:id,name,type,status',
                'unit:id,unit_number,plate_number',
                'route:id,route_number,name,status'
            ])
            ->whereBetween('schedule_date', [$period2StartDate, $period2EndDate])
            ->get()
            ->groupBy(['unit_id', 'shift', 'driver_id']);

        // Get driver leave data for the month
        $leaveData = DB::table('leave_requests')
            ->select('driver_id', 'start_date', 'end_date')
            ->where('status', 'approved')
            ->where(function($query) use ($period1StartDate, $period2EndDate) {
                $query->whereBetween('start_date', [$period1StartDate, $period2EndDate])
                    ->orWhereBetween('end_date', [$period1StartDate, $period2EndDate])
                    ->orWhere(function($q) use ($period1StartDate, $period2EndDate) {
                        $q->where('start_date', '<=', $period1StartDate)
                          ->where('end_date', '>=', $period2EndDate);
                    });
            })
            ->get();

        // Format data for the matrix view
        $period1Data = $this->formatMatrixData($units, $period1Schedules, $period1StartDate, $period1EndDate, $leaveData);
        $period2Data = $this->formatMatrixData($units, $period2Schedules, $period2StartDate, $period2EndDate, $leaveData);

        // Get unassigned units and drivers
        $assignedUnitIds = collect($period1Data)->merge($period2Data)->pluck('unit.id')->unique()->toArray();
        $assignedDriverIds = collect($period1Data)->merge($period2Data)->pluck('driver.id')->unique()->toArray();

        $unassignedUnits = $units->whereNotIn('id', $assignedUnitIds)->values();
        $unassignedDrivers = $drivers->whereNotIn('id', $assignedDriverIds)->values();

        return response()->json([
            'success' => true,
            'period1' => $period1Data,
            'period2' => $period2Data,
            'month' => $month,
            'year' => $year,
            'unassignedUnits' => $unassignedUnits,
            'unassignedDrivers' => $unassignedDrivers,
            'leaveData' => $leaveData
        ]);
    }

    /**
     * Format data for the matrix view
     *
     * @param Collection $units
     * @param Collection $schedules
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    private function formatMatrixData($units, $schedules, $startDate, $endDate, $leaveData = null)
    {
        $result = [];

        // Get all routes for organizing by route_number
        $routes = Route::active()->orderBy('route_number')->get();

        // Get all dates between start and end date
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $dates = [];

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $dates[] = $date->format('Y-m-d');
        }

        // First organize all schedules by unit_id, route_id and shift
        $organizedSchedules = [];

        // Schedules are grouped by unit_id, shift, driver_id so we need to iterate the nested structure
        foreach ($schedules as $unitId => $byUnit) {
            foreach ($byUnit as $shift => $byShift) {
                foreach ($byShift as $driverId => $driverSchedules) {
                    // Get the first schedule to access relationships
                    if ($driverSchedules->isEmpty()) {
                        continue;
                    }

                    $schedule = $driverSchedules->first();
                    $routeId = $schedule->route_id;

                    $key = "{$unitId}-{$routeId}-{$shift}-{$driverId}";
                    if (!isset($organizedSchedules[$key])) {
                        $organizedSchedules[$key] = [
                            'unit' => $schedule->unit,
                            'route' => $schedule->route, // Keep this for grouping
                            'driver' => $schedule->driver,
                            'shift' => $shift,
                            'schedules' => [],
                            'on_leave_dates' => [], // Will store dates when driver is on leave
                            'backup_schedules' => [] // Will store backup driver schedules
                        ];
                    }

                    // Add all schedules for this combination
                    foreach ($driverSchedules as $schedule) {
                        $organizedSchedules[$key]['schedules'][] = $schedule;
                        
                        // If this schedule has a backup driver, process it
                        if ($schedule->backup_driver_id) {
                            // Create a unique key for the backup driver
                            $backupKey = "{$unitId}-{$routeId}-{$shift}-{$schedule->backup_driver_id}-backup";
                            
                            // If this is the first backup schedule for this combination, initialize it
                            if (!isset($organizedSchedules[$backupKey])) {
                                $organizedSchedules[$backupKey] = [
                                    'unit' => $schedule->unit,
                                    'route' => $schedule->route,
                                    'driver' => $schedule->backupDriver,
                                    'shift' => $shift,
                                    'schedules' => [],
                                    'on_leave_dates' => [],
                                    'backup_schedules' => [],
                                    'is_backup' => true,
                                    'primary_driver_id' => $schedule->driver_id
                                ];
                            }
                            
                            // Add this schedule to the backup driver's schedules
                            $organizedSchedules[$backupKey]['schedules'][] = $schedule;
                        }
                    }
                    
                    // Process leave data for this driver if available
                    if ($leaveData) {
                        $driverLeaves = $leaveData->where('driver_id', $driverId);
                        
                        foreach ($driverLeaves as $leave) {
                            $leaveStart = Carbon::parse($leave->start_date);
                            $leaveEnd = Carbon::parse($leave->end_date);
                            
                            // Add all dates in the leave period to the on_leave_dates array
                            for ($leaveDate = $leaveStart->copy(); $leaveDate->lte($leaveEnd); $leaveDate->addDay()) {
                                $leaveDateStr = $leaveDate->format('Y-m-d');
                                if (in_array($leaveDateStr, $dates)) {
                                    $organizedSchedules[$key]['on_leave_dates'][] = $leaveDateStr;
                                }
                            }
                        }
                    }
                }
            }
        }

        // Now group by route_number
        foreach ($routes as $route) {
            $routeRows = [];

            // Find all schedules for this route
            foreach ($organizedSchedules as $key => $data) {
                if ($data['route']->id === $route->id) {
                    $routeRows[] = $data;
                }
            }

            // Skip routes with no schedules
            if (empty($routeRows)) {
                continue;
            }

            // Sort by unit_number and then shift
            usort($routeRows, function($a, $b) {
                $unitCompare = strnatcmp($a['unit']->unit_number, $b['unit']->unit_number);
                if ($unitCompare !== 0) {
                    return $unitCompare;
                }

                // If same unit, sort by shift (morning first)
                if ($a['shift'] === 'pagi' || $a['shift'] === 'morning') {
                    return -1;
                } elseif ($b['shift'] === 'pagi' || $b['shift'] === 'morning') {
                    return 1;
                }

                return 0;
            });

            // Add to result
            foreach ($routeRows as $row) {
                $result[] = $row;
            }
        }

        return $result;
    }

    /**
     * Save an individual schedule (used by the matrix view checkboxes)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveIndividualSchedule(Request $request)
    {
        try {
            // Convert the checked value to a proper boolean
            $isChecked = filter_var($request->input('checked'), FILTER_VALIDATE_BOOLEAN);
            
            // Validate the request
            $validated = $request->validate([
                'date' => 'required|date',
                'driver_id' => 'required|exists:drivers,id',
                'unit_id' => 'required|exists:units,id',
                'shift' => 'required|in:pagi,siang',
                'route_id' => 'required|exists:routes,id',
            ]);
            
            // Add the properly converted boolean value
            $validated['checked'] = $isChecked;

            // Find existing schedule for this unit, date, and shift
            $schedule = Schedule::where('schedule_date', $validated['date'])
                ->where('unit_id', $validated['unit_id'])
                ->where('shift', $validated['shift'])
                ->first();

            // If checked, create or update the schedule
            if ($validated['checked']) {
                if (!$schedule) {
                    // Create new schedule
                    $schedule = new Schedule([
                        'schedule_date' => $validated['date'],
                        'driver_id' => $validated['driver_id'],
                        'unit_id' => $validated['unit_id'],
                        'shift' => $validated['shift'],
                        'route_id' => $validated['route_id'],
                        'status' => 'active'
                    ]);
                    $schedule->save();
                } else {
                    // Update existing schedule with new driver
                    $schedule->driver_id = $validated['driver_id'];
                    $schedule->route_id = $validated['route_id'];
                    $schedule->save();
                }
            } else {
                // If unchecked and schedule exists, delete it
                if ($schedule) {
                    $schedule->delete();
                }
            }

            return response()->json([
                'success' => true,
                'checked' => $validated['checked'],
                'message' => $validated['checked'] ? 'Schedule saved successfully' : 'Schedule removed successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error saving individual schedule: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to save schedule: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Save data from the matrix view
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveMatrix(Request $request)
    {
        // Validate the request
        $validated = $request->validate([
            'matrix' => 'required|array',
            'matrix.*.unit_id' => 'required|exists:units,id',
            'matrix.*.date' => 'required|date',
            'matrix.*.shift' => 'required|in:pagi,siang',
        ]);

        $success = 0;
        $failed = 0;
        $errorMessages = [];

        // Process each schedule item
        foreach ($validated['matrix'] as $item) {
            try {
                // Check if schedule exists for this unit, date, and shift
                $schedule = Schedule::where('unit_id', $item['unit_id'])
                    ->where('schedule_date', $item['date'])
                    ->where('shift', $item['shift'])
                    ->first();

                if ($schedule) {
                    // Schedule already exists, no need to create it
                    $success++;
                } else {
                    // Need to assign a driver to this unit for this date and shift
                    // Get unit with its routes
                    $unit = Unit::with('routes')->findOrFail($item['unit_id']);

                    // Check if unit is in renops for this date (should not be scheduled)
                    $inRenops = UnitRenops::where('unit_id', $unit->id)
                        ->whereDate('date', $item['date'])
                        ->exists();

                    if ($inRenops) {
                        $errorMessages[] = "Unit {$unit->unit_number} is in renops for {$item['date']} and cannot be scheduled";
                        $failed++;
                        continue;
                    }

                    // Get the first route for this unit
                    $route = $unit->routes->first();
                    if (!$route) {
                        $errorMessages[] = "No route found for unit {$unit->unit_number}";
                        $failed++;
                        continue;
                    }

                    // Find a suitable driver
                    // First try with fixed drivers (batangan)
                    $driver = Driver::batangan()
                        ->active()
                        ->whereHas('units', function($query) use ($unit) {
                            $query->where('units.id', $unit->id);
                        })
                        ->whereHas('routes', function($query) use ($route) {
                            $query->where('routes.id', $route->id);
                        })
                        ->whereDoesntHave('schedules', function ($query) use ($item) {
                            $query->where('schedule_date', $item['date'])
                                ->where(function ($q) use ($item) {
                                    // Check both shift and if previous day was evening shift
                                    $q->where('shift', $item['shift']);

                                    // If morning shift, can't have had evening shift yesterday
                                    if ($item['shift'] === 'pagi') {
                                        $previousDate = Carbon::parse($item['date'])->subDay()->format('Y-m-d');
                                        $q->orWhere(function ($subQ) use ($previousDate) {
                                            $subQ->where('schedule_date', $previousDate)
                                                ->where('shift', 'siang');
                                        });
                                    }
                                });
                        })
                        ->first();

                    // If no fixed driver is available, try non-fixed drivers (cadangan)
                    if (!$driver) {
                        $driver = Driver::cadangan()
                            ->active()
                            ->whereHas('units', function($query) use ($unit) {
                                $query->where('units.id', $unit->id);
                            })
                            ->whereHas('routes', function($query) use ($route) {
                                $query->where('routes.id', $route->id);
                            })
                            ->whereDoesntHave('schedules', function ($query) use ($item) {
                                $query->where('schedule_date', $item['date'])
                                    ->where(function ($q) use ($item) {
                                        // Check both shift and if previous day was evening shift
                                        $q->where('shift', $item['shift']);

                                        // If morning shift, can't have had evening shift yesterday
                                        if ($item['shift'] === 'pagi') {
                                            $previousDate = Carbon::parse($item['date'])->subDay()->format('Y-m-d');
                                            $q->orWhere(function ($subQ) use ($previousDate) {
                                                $subQ->where('schedule_date', $previousDate)
                                                    ->where('shift', 'siang');
                                            });
                                        }
                                    });
                            })
                            ->first();
                    }

                    // If no driver is available
                    if (!$driver) {
                        $errorMessages[] = "No suitable driver found for unit {$unit->unit_number} on {$item['date']} ({$item['shift']} shift)";
                        $failed++;
                        continue;
                    }

                    // Create the schedule
                    Schedule::create([
                        'unit_id' => $item['unit_id'],
                        'route_id' => $route->id,
                        'driver_id' => $driver->id,
                        'schedule_date' => $item['date'],
                        'shift' => $item['shift'],
                        'status' => 'scheduled',
                    ]);

                    $success++;
                }
            } catch (\Exception $e) {
                $failed++;
                $errorMessages[] = "Error for unit {$item['unit_id']} on {$item['date']} ({$item['shift']} shift): " . $e->getMessage();
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Successfully created {$success} schedules. {$failed} failed.",
            'errors' => $errorMessages
        ]);
    }

    /**
     * Save a batch of individual schedules
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveIndividualBatch(Request $request)
    {
        // Validate the request
        $validated = $request->validate([
            'assignments' => 'required|array',
            'assignments.*.date' => 'required|date',
            'assignments.*.unit_id' => 'required|exists:units,id',
            'assignments.*.driver_id' => 'required|exists:drivers,id',
            'assignments.*.route_id' => 'required|exists:routes,id',
            'assignments.*.shift' => 'required|in:pagi,siang',
        ]);

        // Log the request for debugging
        \Log::info('Batch Schedule Save Request', $validated);

        $results = [];
        $success = true;

        try {
            // Process each assignment in the batch
            foreach ($validated['assignments'] as $index => $assignment) {
                // Check if schedule exists for this unit, date, and shift
                $schedule = Schedule::where('unit_id', $assignment['unit_id'])
                    ->where('schedule_date', $assignment['date'])
                    ->where('shift', $assignment['shift'])
                    ->first();

                if ($schedule) {
                    // Schedule already exists, no need to create it
                    $success++;
                } else {
                    // Need to assign a driver to this unit for this date and shift
                    // Get unit with its routes
                    $unit = Unit::with('routes')->findOrFail($assignment['unit_id']);

                    // Check if unit is in renops for this date (should not be scheduled)
                    $inRenops = UnitRenops::where('unit_id', $unit->id)
                        ->whereDate('date', $assignment['date'])
                        ->exists();

                    if ($inRenops) {
                        $errorMessages[] = "Unit {$unit->unit_number} is in renops for {$assignment['date']} and cannot be scheduled";
                        $failed++;
                        continue;
                    }

                    // Get the first route for this unit
                    $route = $unit->routes->first();
                    if (!$route) {
                        $errorMessages[] = "No route found for unit {$unit->unit_number}";
                        $failed++;
                        continue;
                    }

                    // Find a suitable driver
                    // First try with fixed drivers (batangan)
                    $driver = Driver::batangan()
                        ->active()
                        ->whereHas('units', function($query) use ($unit) {
                            $query->where('units.id', $unit->id);
                        })
                        ->whereHas('routes', function($query) use ($route) {
                            $query->where('routes.id', $route->id);
                        })
                        ->whereDoesntHave('schedules', function ($query) use ($assignment) {
                            $query->where('schedule_date', $assignment['date'])
                                ->where(function ($q) use ($assignment) {
                                    // Check both shift and if previous day was evening shift
                                    $q->where('shift', $assignment['shift']);

                                    // If morning shift, can't have had evening shift yesterday
                                    if ($assignment['shift'] === 'pagi') {
                                        $previousDate = Carbon::parse($assignment['date'])->subDay()->format('Y-m-d');
                                        $q->orWhere(function ($subQ) use ($previousDate) {
                                            $subQ->where('schedule_date', $previousDate)
                                                ->where('shift', 'siang');
                                        });
                                    }
                                });
                        })
                        ->first();

                    // If no fixed driver is available, try non-fixed drivers (cadangan)
                    if (!$driver) {
                        $driver = Driver::cadangan()
                            ->active()
                            ->whereHas('units', function($query) use ($unit) {
                                $query->where('units.id', $unit->id);
                            })
                            ->whereHas('routes', function($query) use ($route) {
                                $query->where('routes.id', $route->id);
                            })
                            ->whereDoesntHave('schedules', function ($query) use ($assignment) {
                                $query->where('schedule_date', $assignment['date'])
                                    ->where(function ($q) use ($assignment) {
                                        // Check both shift and if previous day was evening shift
                                        $q->where('shift', $assignment['shift']);

                                        // If morning shift, can't have had evening shift yesterday
                                        if ($assignment['shift'] === 'pagi') {
                                            $previousDate = Carbon::parse($assignment['date'])->subDay()->format('Y-m-d');
                                            $q->orWhere(function ($subQ) use ($previousDate) {
                                                $subQ->where('schedule_date', $previousDate)
                                                    ->where('shift', 'siang');
                                            });
                                        }
                                    });
                            })
                            ->first();
                    }

                    // If no driver is available
                    if (!$driver) {
                        $errorMessages[] = "No suitable driver found for unit {$unit->unit_number} on {$assignment['date']} ({$assignment['shift']} shift)";
                        $failed++;
                        continue;
                    }

                    // Create the schedule
                    Schedule::create([
                        'unit_id' => $assignment['unit_id'],
                        'route_id' => $route->id,
                        'driver_id' => $driver->id,
                        'schedule_date' => $assignment['date'],
                        'shift' => $assignment['shift'],
                        'status' => 'scheduled',
                    ]);

                    $success++;
                }
            }

            return response()->json([
                'success' => $success,
                'message' => "Successfully created {$success} schedules. {$failed} failed.",
                'errors' => $errorMessages
            ]);
        } catch (\Exception $e) {
            \Log::error('Error saving batch schedules', [
                'error' => $e->getMessage(),
                'data' => $validated
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'errors' => $errorMessages
            ], 500);
        }
    }

    /**
     * Get drivers qualified for a specific route and unit combination
     * 
     * @param int $routeId
     * @param int $unitId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getQualifiedDrivers($routeId, $unitId)
    {
        // Get drivers qualified for both the route and unit
        $qualifiedDrivers = Driver::whereHas('routes', function($query) use ($routeId) {
                $query->where('routes.id', $routeId);
            })
            ->whereHas('units', function($query) use ($unitId) {
                $query->where('units.id', $unitId);
            })
            ->with(['routes', 'units'])
            ->get();
        
        // Get drivers qualified for the route only
        $routeOnlyDrivers = Driver::whereHas('routes', function($query) use ($routeId) {
                $query->where('routes.id', $routeId);
            })
            ->whereDoesntHave('units', function($query) use ($unitId) {
                $query->where('units.id', $unitId);
            })
            ->with(['routes', 'units'])
            ->get();
        
        // Get drivers qualified for the unit only
        $unitOnlyDrivers = Driver::whereHas('units', function($query) use ($unitId) {
                $query->where('units.id', $unitId);
            })
            ->whereDoesntHave('routes', function($query) use ($routeId) {
                $query->where('routes.id', $routeId);
            })
            ->with(['routes', 'units'])
            ->get();
        
        // Get unqualified drivers (neither route nor unit)
        $unqualifiedDrivers = Driver::whereDoesntHave('routes', function($query) use ($routeId) {
                $query->where('routes.id', $routeId);
            })
            ->whereDoesntHave('units', function($query) use ($unitId) {
                $query->where('units.id', $unitId);
            })
            ->with(['routes', 'units'])
            ->get();
        
        return response()->json([
            'qualified' => $qualifiedDrivers,
            'routeOnly' => $routeOnlyDrivers,
            'unitOnly' => $unitOnlyDrivers,
            'unqualified' => $unqualifiedDrivers
        ]);
    }

    /**
     * Get a list of all routes for dropdown selection
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRoutesList()
    {
        $routes = Route::orderBy('route_number')->get();
        return response()->json($routes);
    }

    /**
     * Get a list of all units for dropdown selection
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUnitsList()
    {
        $units = Unit::orderBy('unit_number')->get();
        return response()->json($units);
    }

    /**
     * Get a list of all drivers for dropdown selection
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDriversList()
    {
        $drivers = Driver::with(['routes', 'units'])->orderBy('name')->get();
        return response()->json($drivers);
    }
}
