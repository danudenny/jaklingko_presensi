<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\Driver;
use App\Models\Route;
use App\Models\Unit;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Services\ScheduleGeneratorService;
use App\Exports\SchedulesExport;
use App\Exports\SchedulesPdfExport;
use Maatwebsite\Excel\Facades\Excel;

class ScheduleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $driverIds = $request->input('driver_id', []);
        $unitIds = $request->input('unit_id', []);
        $perPage = $request->input('per_page', 15); // Default to 15 items per page
        
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
        
        // Get all schedules for counting by shift (needed for the summary)
        $allSchedules = (clone $query)->get();
        
        // Get paginated results
        $schedules = $query->orderBy('schedule_date')
                          ->orderBy('shift')
                          ->paginate($perPage)
                          ->withQueryString(); // Preserve query parameters in pagination links
        
        $drivers = Driver::active()->orderBy('name')->get();
        $units = Unit::active()->orderBy('unit_number')->get();
        
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
        
        // Get selected drivers and units for filter display
        $selectedDrivers = [];
        $selectedUnits = [];
        
        if (!empty($driverIds)) {
            $selectedDrivers = Driver::whereIn('id', $driverIds)->get();
        }
        
        if (!empty($unitIds)) {
            $selectedUnits = Unit::whereIn('id', $unitIds)->get();
        }
        
        return view('modules.admin.schedules.index', compact(
            'schedules', 
            'startDate', 
            'endDate', 
            'drivers', 
            'driverIds', 
            'units', 
            'unitIds',
            'morningCount',
            'eveningCount',
            'batanganCount',
            'cadanganCount',
            'allSchedules',
            'selectedDrivers',
            'selectedUnits'
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
}
