<?php

namespace App\Services;

use App\Models\Driver;
use App\Models\DriverHistory;
use App\Models\DriverScheduleHistory;
use App\Models\Route;
use App\Models\Schedule;
use App\Models\Unit;
use App\Models\Holiday;
use App\Models\RenopsSettings;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ScheduleGeneratorService
{
    protected $routes;
    protected $units;
    protected $batanganDrivers;        // Batangan drivers (D)
    protected $cadanganDrivers;     // Cadangan drivers (DD)
    protected $messages = [];
    protected $success = 0;
    protected $failed = 0;
    protected $periodDays = 15;     // 15-day scheduling period
    protected $weekendDriversPool = []; // Track drivers allocated for weekend shifts
    protected $renopsSettings; // Renops settings from database
    protected $driverScheduleSettings = []; // Driver schedule settings by type
    protected $batanganSettings; // Batangan driver settings
    protected $cadanganSettings; // Cadangan driver settings
    protected $unitDayOffs = []; // Cache of unit day offs from unit_renops table
    protected $driverUnitMap = []; // Map of driver assignments to units for quick lookup
    protected $unitAssignmentsCache = []; // Cache of unit assignments for quick lookup
    protected $routeAssignmentsCache = []; // Cache of route assignments for quick lookup
    protected $existingSchedulesCache = []; // Cache of existing schedules for the period
    protected $leaveRequestsCache = []; // Cache of leave requests for the period

    protected $utilityService;
    protected $driverSelectionService;
    protected $batanganSchedulePlan = [];

    public function __construct(
        ScheduleGeneratorUtilityService $utilityService,
        DriverSelectionService $driverSelectionService
    ) {
        $this->utilityService = $utilityService;
        $this->driverSelectionService = $driverSelectionService;
    }

    public function generateSchedules(string $startDate, string $endDate): array
    {
        $startTime = microtime(true);
        $startDate = Carbon::parse($startDate)->startOfDay();
        $endDate = Carbon::parse($endDate)->endOfDay();

        $results = [
            'success' => 0,
            'failed' => 0,
            'messages' => []
        ];

        $this->loadData($results);

        // Generate batangan schedule plan before scheduling loop
        $this->generateBatanganSchedulePlan($startDate, $endDate);

        if ($this->units->isEmpty()) {
            $results['messages'][] = "No units available for scheduling. Cannot generate schedules.";
            return $results;
        }

        if ($this->batanganDrivers->isEmpty() && $this->cadanganDrivers->isEmpty()) {
            $results['messages'][] = "No drivers available for scheduling. Cannot generate schedules.";
            return $results;
        }

        $this->renopsSettings = RenopsSettings::getCurrentSettings();
        $this->utilityService->loadDriverScheduleSettings(
            $this->driverScheduleSettings,
            $this->batanganSettings,
            $this->cadanganSettings,
            $this->messages
        );

        $this->utilityService->preloadUnitDayOffs($startDate, $endDate, $this->unitDayOffs);
        $this->utilityService->precacheUnitAssignments(
            $this->units,
            $this->batanganDrivers,
            $this->cadanganDrivers,
            $this->unitAssignmentsCache
        );

        $this->utilityService->precacheRouteAssignments(
            $this->routes,
            $this->batanganDrivers,
            $this->cadanganDrivers,
            $this->routeAssignmentsCache
        );

        $this->utilityService->precacheExistingSchedules($startDate, $endDate, $this->existingSchedulesCache);
        $this->utilityService->precacheLeaveRequests($startDate, $endDate, $this->leaveRequestsCache);

        $currentDate = $startDate->copy();
        $totalDays = $startDate->diffInDays($endDate) + 1;
        $daysProcessed = 0;

        set_time_limit(300); // 5 minutes

        $this->messages[] = "Starting schedule generation for {$totalDays} days from {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}";

        // Process in smaller chunks to avoid timeouts
        $chunkSize = 5; // Process 5 days at a time
        $chunks = ceil($totalDays / $chunkSize);

        // Create a lookup array for drivers on leave by date
        $driversOnLeaveByDate = [];
        foreach ($this->leaveRequestsCache as $leave) {
            $start = Carbon::parse($leave->start_date);
            $end = Carbon::parse($leave->end_date);

            for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                $dateStr = $date->format('Y-m-d');
                if (!isset($driversOnLeaveByDate[$dateStr])) {
                    $driversOnLeaveByDate[$dateStr] = [];
                }
                $driversOnLeaveByDate[$dateStr][] = $leave->driver_id;
            }
        }

        for ($chunk = 0; $chunk < $chunks; $chunk++) {
            $chunkStartDate = $startDate->copy()->addDays($chunk * $chunkSize);
            $chunkEndDate = $startDate->copy()->addDays(min(($chunk + 1) * $chunkSize - 1, $totalDays - 1));

            if ($chunkEndDate->gt($endDate)) {
                $chunkEndDate = $endDate->copy();
            }

            $this->messages[] = "Processing chunk {$chunk} of {$chunks}: {$chunkStartDate->format('Y-m-d')} to {$chunkEndDate->format('Y-m-d')}";

            $currentDate = $chunkStartDate->copy();

            while ($currentDate->lte($chunkEndDate)) {
                $dateStr = $currentDate->format('Y-m-d');
                $daysProcessed++;

                // Reset weekend drivers pool for each day
                $this->weekendDriversPool = [];

                // Get unavailable units for this day from unit_renops table using utility service
                $unavailableUnitIds = $this->utilityService->getUnavailableUnitsForDay($dateStr, $this->unitDayOffs);

                // Determine resource percentage based on day type
                $resourcePercentage = $this->utilityService->getResourcePercentageFromUnitRenops($dateStr, [
                    'weekend_percentage' => $this->renopsSettings ? $this->renopsSettings->weekend_threshold : 80,
                    'holiday_percentage' => $this->renopsSettings ? $this->renopsSettings->holiday_threshold : 80,
                ]);

                // Calculate counts for logging
                $totalUnitsCount = $this->units->count();
                $unavailableUnitsCount = count($unavailableUnitIds);
                $availableUnitsCount = $totalUnitsCount - $unavailableUnitsCount;

                // Log the unit availability for this date
                $this->messages[] = "Processing date: {$dateStr}, Day: {$currentDate->format('l')}, " .
                    "Total Units: {$totalUnitsCount}, " .
                    "Day Offs (from unit_renops): {$unavailableUnitsCount}, " .
                    "Available Units: {$availableUnitsCount}";

                // Get existing schedules for this date
                $existingSchedulesForDate = isset($this->existingSchedulesCache[$dateStr]) 
                    ? collect($this->existingSchedulesCache[$dateStr]) 
                    : collect();
                    
                $scheduledUnitIdsMorning = $existingSchedulesForDate->where('shift', 'pagi')->pluck('unit_id')->toArray();
                $scheduledUnitIdsEvening = $existingSchedulesForDate->where('shift', 'siang')->pluck('unit_id')->toArray();
                $scheduledDriverIds = $existingSchedulesForDate->pluck('driver_id')->toArray();

                // Get drivers on leave for this date
                $driversOnLeaveIds = $driversOnLeaveByDate[$dateStr] ?? [];
                
                // Track newly scheduled drivers for this day to ensure one driver can't work both shifts
                $dayDriversScheduled = []; 
                
                $this->messages[] = "Scheduling both shifts for {$dateStr}";

                // Process morning shift with optimized data - batangan first, then cadangan
                $morningResults = $this->generateShiftSchedules(
                    $dateStr,
                    'pagi',
                    $resourcePercentage,
                    $startDate->format('Y-m-d'),
                    $endDate->format('Y-m-d'),
                    $unavailableUnitIds,
                    $scheduledUnitIdsMorning,
                    $scheduledDriverIds,
                    $driversOnLeaveIds,
                    $dayDriversScheduled
                );
                
                // Log morning shift results
                $this->messages[] = "Morning shift: Scheduled {$morningResults['units_scheduled']} units with {$morningResults['drivers_scheduled']} drivers for {$dateStr}";
                
                // Update drivers scheduled for the day before scheduling afternoon shift
                $scheduledDriverIdsForAfternoon = array_merge($scheduledDriverIds, array_keys($dayDriversScheduled));

                // Process evening shift with optimized data - batangan first, then cadangan
                $afternoonResults = $this->generateShiftSchedules(
                    $dateStr,
                    'siang',
                    $resourcePercentage,
                    $startDate->format('Y-m-d'),
                    $endDate->format('Y-m-d'),
                    $unavailableUnitIds,
                    $scheduledUnitIdsEvening,
                    $scheduledDriverIdsForAfternoon,
                    $driversOnLeaveIds,
                    $dayDriversScheduled
                );
                
                // Log afternoon shift results
                $this->messages[] = "Afternoon shift: Scheduled {$afternoonResults['units_scheduled']} units with {$afternoonResults['drivers_scheduled']} drivers for {$dateStr}";
                
                // Calculate available units for this day
                $availableUnitsCount = count($this->units) - count($unavailableUnitIds);
                
                // Check if we have scheduled both shifts for all available units
                $unitsWithMorningShift = $morningResults['scheduled_units'] ?? [];
                $unitsWithAfternoonShift = $afternoonResults['scheduled_units'] ?? [];
                
                // Units missing morning shifts
                $unitsMissingMorning = array_diff(
                    array_column($this->units->whereNotIn('id', $unavailableUnitIds)->all(), 'id'), 
                    $unitsWithMorningShift
                );
                
                // Units missing afternoon shifts
                $unitsMissingAfternoon = array_diff(
                    array_column($this->units->whereNotIn('id', $unavailableUnitIds)->all(), 'id'), 
                    $unitsWithAfternoonShift
                );
                
                // Report on units missing shifts
                if (count($unitsMissingMorning) > 0) {
                    $this->messages[] = "Warning: {$dateStr} has " . count($unitsMissingMorning) . " units missing morning shifts";
                }
                
                if (count($unitsMissingAfternoon) > 0) {
                    $this->messages[] = "Warning: {$dateStr} has " . count($unitsMissingAfternoon) . " units missing afternoon shifts";
                }
                
                // If we have units missing shifts, try to assign drivers to those units
                if (count($unitsMissingMorning) > 0 || count($unitsMissingAfternoon) > 0) {
                    // Update the list of scheduled driver IDs including both shifts
                    $updatedScheduledDriverIds = array_merge($scheduledDriverIds, array_keys($dayDriversScheduled));
                    
                    // Initialize reschedule results
                    $rescheduleResults = [
                        'morning_added' => 0,
                        'afternoon_added' => 0
                    ];
                    
                    // Attempt to find drivers for units missing shifts
                    $rescheduleResults = $this->attemptRescheduleForMissingShifts(
                        $dateStr,
                        $unitsMissingMorning,
                        $unitsMissingAfternoon,
                        $updatedScheduledDriverIds,
                        $driversOnLeaveIds,
                        $dayDriversScheduled
                    );
                    
                    // Log results of the rescheduling attempt
                    if ($rescheduleResults['morning_added'] > 0 || $rescheduleResults['afternoon_added'] > 0) {
                        $this->messages[] = "Successfully added {$rescheduleResults['morning_added']} morning shifts and {$rescheduleResults['afternoon_added']} afternoon shifts for {$dateStr}";
                    } else {
                        $this->messages[] = "Could not find drivers for the missing shifts on {$dateStr}";
                    }
                }
                
                // If we still have some units with uneven shift coverage after the rescheduling attempt, log this
                if ((count($unitsMissingMorning) - ($rescheduleResults['morning_added'] ?? 0) > 0) || 
                    (count($unitsMissingAfternoon) - ($rescheduleResults['afternoon_added'] ?? 0) > 0)) {
                    $this->messages[] = "Note: Some units still have uneven shift coverage for {$dateStr} after rescheduling attempt.";
                }

                // Log progress periodically
                if ($daysProcessed % 3 == 0 || $currentDate->equalTo($endDate)) {
                    $progressPercent = round(($daysProcessed / $totalDays) * 100);
                    $this->messages[] = "Processed {$daysProcessed} of {$totalDays} days ({$progressPercent}%)";
                }

                $currentDate->addDay();
            }
        }

        // Balance schedules after all days are processed
        $this->balanceSchedulesInPeriod($startDate, $endDate, $results);

        $endTime = microtime(true);
        $executionTime = round($endTime - $startTime, 2);

        $this->messages[] = "Schedule generation completed in {$executionTime} seconds.";

        $results['success'] = $this->success;
        $results['failed'] = $this->failed;
        $results['messages'] = $this->messages;

        return $results;
    }

    /**
     * Load all necessary data for schedule generation
     *
     * @param array $results
     * @return void
     */
    protected function loadData(array &$results): void
    {
        try {
            // Initialize collections to prevent isEmpty() on null errors
            $this->routes = collect();
            $this->units = collect();
            $this->batanganDrivers = collect();
            $this->cadanganDrivers = collect();
            
            // Load all routes
            $this->routes = Route::all();
            $results['messages'][] = "Loaded " . $this->routes->count() . " routes";

            // Load all units with their routes
            $this->units = Unit::with('routes')->get();
            $results['messages'][] = "Loaded " . $this->units->count() . " units";

            // Load all drivers with their relationships
            $allDrivers = Driver::with(['units', 'routes'])->get();
            $results['messages'][] = "Loaded " . $allDrivers->count() . " drivers";

            // Split drivers by type
            $this->batanganDrivers = $allDrivers->where('type', 'batangan');
            $this->cadanganDrivers = $allDrivers->where('type', 'cadangan');

            $results['messages'][] = "Split drivers: " . $this->batanganDrivers->count() . " batangan, " . $this->cadanganDrivers->count() . " cadangan";

            // Create a map of driver assignments to units for quick lookup
            $this->driverUnitMap = [];
            
            // First try to get assignments from the driver's loaded relationships
            foreach ($allDrivers as $driver) {
                if ($driver->units && $driver->units->isNotEmpty()) {
                    $this->driverUnitMap[$driver->id] = $driver->units->pluck('id')->toArray();
                }
            }

            $results['messages'][] = "Created driver-unit map with " . count($this->driverUnitMap) . " entries";

        } catch (\Exception $e) {
            $results['messages'][] = "ERROR loading data: " . $e->getMessage();
            Log::error("Error loading data for schedule generation: " . $e->getMessage());
            Log::error($e->getTraceAsString());
        }
    }

    /**
     * Generate a randomized, valid schedule plan for each batangan driver/unit
     * @param Carbon|string $startDate
     * @param Carbon|string $endDate
     */
    protected function generateBatanganSchedulePlan($startDate, $endDate)
    {
        $start = Carbon::parse($startDate)->copy();
        $end = Carbon::parse($endDate)->copy();
        $days = [];
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $days[] = $date->format('Y-m-d');
        }
        $totalDays = count($days);
        $targetDays = isset($this->batanganSettings['min_schedules']) ? $this->batanganSettings['min_schedules'] : min(13, $totalDays);
        foreach ($this->batanganDrivers as $driver) {
            foreach ($driver->units as $unit) {
                // Randomly pick days for this driver/unit
                $selectedDays = $days;
                shuffle($selectedDays);
                $selectedDays = array_slice($selectedDays, 0, $targetDays);
                sort($selectedDays); // Sort for easier lookup
                // Assign shifts for selected days, respecting shift rules
                $plan = [];
                $lastShift = null;
                $lastDay = null;
                foreach ($selectedDays as $i => $day) {
                    if ($lastDay) {
                        $prevIdx = array_search($lastDay, $days);
                        $currIdx = array_search($day, $days);
                        $gap = $currIdx - $prevIdx;
                        if ($gap > 1) {
                            // If there was a gap, reset lastShift
                            $lastShift = null;
                        }
                    }
                    // Shift assignment logic
                    if ($lastShift === 'siang') {
                        $shift = 'siang';
                    } else {
                        $shift = (rand(0, 1) === 0) ? 'pagi' : 'siang';
                    }
                    $plan[$day] = $shift;
                    $lastShift = $shift;
                    $lastDay = $day;
                }
                $this->batanganSchedulePlan[$unit->id][$driver->id] = $plan;
            }
        }
    }

    protected function generateShiftSchedules(
        string $dateStr,
        string $shift,
        int $resourcePercentage,
        string $periodStartDate,
        string $periodEndDate,
        array $unavailableUnitIds,
        array $scheduledUnitIds,
        array $scheduledDriverIds,
        array $driversOnLeaveIds,
        array &$dayDriversScheduled = []
    ): array {
        $this->messages[] = "Generating {$shift} shift schedules for {$dateStr}";
        
        $result = [
            'units_scheduled' => 0,
            'drivers_scheduled' => 0,
            'scheduled_units' => []  // Track unit IDs that were scheduled
        ];

        // Create sets for faster lookups
        $scheduledDriverIdsSet = array_flip($scheduledDriverIds);
        $scheduledUnitIdsSet = array_flip($scheduledUnitIds);
        $driversOnLeaveIdsSet = array_flip($driversOnLeaveIds);

        // Track which shift a driver is assigned to for the day
        static $scheduledDriverShiftsSet = [];
        if (!isset($scheduledDriverShiftsSet[$dateStr])) {
            $scheduledDriverShiftsSet[$dateStr] = [];
        }

        // Get previous day's and two days ago schedules for continuity checks
        $previousDate = Carbon::parse($dateStr)->subDay();
        $previousDateStr = $previousDate->format('Y-m-d');
        $previousDaySchedules = [];
        
        if (isset($this->existingSchedulesCache[$previousDateStr])) {
            foreach ($this->existingSchedulesCache[$previousDateStr] as $schedule) {
                $previousDaySchedules[$schedule->driver_id] = [
                    'unit_id' => $schedule->unit_id,
                    'shift' => $schedule->shift
                ];
            }
        }
        
        $twoDaysAgo = Carbon::parse($dateStr)->subDays(2);
        $twoDaysAgoStr = $twoDaysAgo->format('Y-m-d');
        $twoDaysAgoSchedules = [];
        
        if (isset($this->existingSchedulesCache[$twoDaysAgoStr])) {
            foreach ($this->existingSchedulesCache[$twoDaysAgoStr] as $schedule) {
                $twoDaysAgoSchedules[$schedule->driver_id] = [
                    'unit_id' => $schedule->unit_id,
                    'shift' => $schedule->shift
                ];
            }
        }

        // Get driver schedule counts for the period
        $driverScheduleCounts = $this->getDriverScheduleCountsForPeriod($periodStartDate, $periodEndDate);
        
        // Sort units by unit_number for consistent processing
        $sortedUnits = $this->units->sortBy('unit_number');
        $schedulesToCreate = [];
        
        // First assign batangan (fixed) drivers to their specific units
        foreach ($sortedUnits as $unit) {
            // Skip if unit is unavailable for this day
            if (in_array($unit->id, $unavailableUnitIds)) {
                continue;
            }

            // Skip if unit already has a schedule for this shift
            if (isset($scheduledUnitIdsSet[$unit->id])) {
                continue;
            }

            // Find batangan (fixed) driver for the unit
            $driver = $this->driverSelectionService->findBatanganDriverForUnit(
                $unit,
                $dateStr,
                $shift,
                $scheduledDriverIdsSet,
                $driversOnLeaveIdsSet,
                $previousDaySchedules,
                $twoDaysAgoSchedules,
                $driverScheduleCounts,
                $this->batanganDrivers,
                $this->unitAssignmentsCache,
                $this->unitDayOffs,
                $this->batanganSettings,
                $this->cadanganSettings,
                $this->messages
            );

            // If a suitable batangan driver was found, create the schedule
            if ($driver) {
                // Check if driver is already scheduled on this day for a different shift
                if (isset($dayDriversScheduled[$driver->id])) {
                    $this->messages[] = "Driver {$driver->name} already scheduled for {$dateStr} ({$dayDriversScheduled[$driver->id]} shift). Skipping assignment to {$shift} shift.";
                    continue;
                }
                
                // Only assign if this day/shift is in the pre-generated plan
                $plan = $this->batanganSchedulePlan[$unit->id][$driver->id] ?? [];
                if (!isset($plan[$dateStr]) || $plan[$dateStr] !== $shift) {
                    continue;
                }
                
                $routeId = $unit->routes->isNotEmpty() ? $unit->routes->first()->id : null;
                if (!$routeId) {
                    $this->messages[] = "Warning: Unit {$unit->unit_number} has no routes. Schedule cannot be created.";
                    continue;
                }
                
                $schedule = [
                    'unit_id' => $unit->id,
                    'driver_id' => $driver->id,
                    'route_id' => $routeId,
                    'schedule_date' => $dateStr,
                    'shift' => $shift,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                
                $schedulesToCreate[] = $schedule;
                $scheduledDriverIdsSet[$driver->id] = true;
                $scheduledUnitIdsSet[$unit->id] = true;
                $scheduledDriverShiftsSet[$dateStr][$driver->id] = $shift; // Mark driver as scheduled for this shift on this day
                $dayDriversScheduled[$driver->id] = $shift; // Also mark in the daily tracking array
                
                if (!isset($driverScheduleCounts[$driver->id])) {
                    $driverScheduleCounts[$driver->id] = 0;
                }
                $driverScheduleCounts[$driver->id]++;
            }
        }

        // Now process remaining units with cadangan (backup) drivers
        foreach ($sortedUnits as $unit) {
            // Skip if unit is unavailable for this day
            if (in_array($unit->id, $unavailableUnitIds)) {
                continue;
            }
            // Skip if unit already has a schedule for this shift
            if (isset($scheduledUnitIdsSet[$unit->id])) {
                continue;
            }
            // Find cadangan (backup) driver for the unit
            $driver = $this->driverSelectionService->findCadanganDriverForUnit(
                $unit,
                $dateStr,
                $shift,
                $scheduledDriverIdsSet,
                $driversOnLeaveIdsSet,
                $previousDaySchedules,
                $twoDaysAgoSchedules,
                $driverScheduleCounts,
                $this->cadanganDrivers,
                $this->unitAssignmentsCache,
                $this->routeAssignmentsCache,
                $this->unitDayOffs,
                $this->batanganSettings,
                $this->cadanganSettings,
                $this->messages
            );
            if ($driver) {
                // Prevent assigning cadangan driver to more than one shift in a day (across all units)
                if (isset($scheduledDriverShiftsSet[$dateStr][$driver->id])) {
                    continue;
                }
                $routeId = $unit->routes->isNotEmpty() ? $unit->routes->first()->id : null;
                if (!$routeId) {
                    $this->messages[] = "Warning: Unit {$unit->unit_number} has no routes. Schedule cannot be created.";
                    continue;
                }
                $schedule = [
                    'unit_id' => $unit->id,
                    'driver_id' => $driver->id,
                    'route_id' => $routeId,
                    'schedule_date' => $dateStr,
                    'shift' => $shift,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $schedulesToCreate[] = $schedule;
                $scheduledDriverIdsSet[$driver->id] = true;
                $scheduledUnitIdsSet[$unit->id] = true;
                $scheduledDriverShiftsSet[$dateStr][$driver->id] = $shift; // Mark driver as scheduled for this shift on this day
                if (!isset($driverScheduleCounts[$driver->id])) {
                    $driverScheduleCounts[$driver->id] = 0;
                }
                $driverScheduleCounts[$driver->id]++;
            }
        }
        
        // Insert all schedules for this shift in a single batch
        if (!empty($schedulesToCreate)) {
            list($success, $failed) = $this->utilityService->createSchedules($schedulesToCreate, $this->messages);
            $this->success += $success;
            $this->failed += $failed;
            
            // Track scheduled units and drivers for this shift
            $scheduledUnitIds = array_unique(array_column($schedulesToCreate, 'unit_id'));
            $result['units_scheduled'] = count($scheduledUnitIds);
            $result['scheduled_units'] = $scheduledUnitIds;
            $result['drivers_scheduled'] = count(array_unique(array_column($schedulesToCreate, 'driver_id')));
            
            // Add scheduled drivers to day tracking
            foreach ($schedulesToCreate as $schedule) {
                // Track which driver was scheduled for this day to prevent assignment to multiple shifts
                $dayDriversScheduled[$schedule['driver_id']] = $schedule['shift'];
            }
        }
        
        return $result;
    }
    
    /**
     * Get driver schedule counts for the period
     *
     * @param string $startDateStr
     * @param string $endDateStr
     * @return array
     */
    protected function getDriverScheduleCountsForPeriod(string $startDateStr, string $endDateStr): array
    {
        $driverCounts = [];
        
        // Query existing schedules for the period
        $existingSchedules = Schedule::select('driver_id', DB::raw('COUNT(*) as count'))
            ->whereBetween('schedule_date', [$startDateStr, $endDateStr])
            ->groupBy('driver_id')
            ->get();
            
        foreach ($existingSchedules as $schedule) {
            $driverCounts[$schedule->driver_id] = $schedule->count;
        }
        
        return $driverCounts;
    }

    /**
     * Balance schedules for drivers in the period to ensure fair distribution
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param array $results
     * @return void
     */
    protected function balanceSchedulesInPeriod(Carbon $startDate, Carbon $endDate, array &$results): void
    {
        $this->messages[] = "Balancing driver schedules in the period if needed...";
        
        // For future implementation: Analyze and redistribute schedules if needed
        // to ensure fair distribution of shifts among drivers
        
        // Create schedule history records for tracking
        $this->createDriverScheduleHistoryRecords($startDate, $endDate);
    }
    
    /**
     * Create driver schedule history records for tracking
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return void
     */
    protected function createDriverScheduleHistoryRecords(Carbon $startDate, Carbon $endDate): void
    {
        try {
            // Get all schedules in the period
            $schedules = Schedule::whereBetween('schedule_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->get();
                
            // Group schedules by driver
            $schedulesByDriver = $schedules->groupBy('driver_id');
            
            // Create history records for each driver
            foreach ($schedulesByDriver as $driverId => $driverSchedules) {
                // Count total schedules and by shift
                $totalSchedules = $driverSchedules->count();
                $morningShifts = $driverSchedules->where('shift', 'pagi')->count();
                $afternoonShifts = $driverSchedules->where('shift', 'siang')->count();
                
                // Get driver schedule settings to determine target count
                $driver = Driver::find($driverId);
                $targetCount = 14; // Default target
                if ($driver) {
                    if ($driver->type === 'batangan' && isset($this->batanganSettings['min_schedules'])) {
                        $targetCount = $this->batanganSettings['min_schedules'];
                    } else if ($driver->type === 'cadangan' && isset($this->cadanganSettings['min_schedules'])) {
                        $targetCount = $this->cadanganSettings['min_schedules'];
                    }
                }
                
                // Create or update history record
                DriverScheduleHistory::updateOrCreate(
                    [
                        'driver_id' => $driverId,
                        'period_start_date' => $startDate->format('Y-m-d'),
                        'period_end_date' => $endDate->format('Y-m-d'),
                    ],
                    [
                        'total_schedules' => $totalSchedules,
                        'morning_shifts' => $morningShifts,
                        'afternoon_shifts' => $afternoonShifts,
                        'target_count' => $targetCount,
                        'target_met' => $totalSchedules >= $targetCount,
                    ]
                );
            }
            
            $this->messages[] = "Created schedule history records for " . count($schedulesByDriver) . " drivers";
        } catch (\Exception $e) {
            $this->messages[] = "Error creating schedule history records: " . $e->getMessage();
            Log::error("Error creating schedule history records: " . $e->getMessage());
            Log::error($e->getTraceAsString());
        }
    }
    
    /**
     * Attempt to find additional drivers for units missing either morning or afternoon shifts
     *
     * @param string $dateStr The date to schedule for
     * @param array $unitsMissingMorning Unit IDs missing morning shifts
     * @param array $unitsMissingAfternoon Unit IDs missing afternoon shifts
     * @param array $scheduledDriverIds Currently scheduled drivers
     * @param array $driversOnLeaveIds Drivers on leave
     * @param array $dayDriversScheduled Tracking of drivers scheduled for this day
     * @return array Result with counts of additional schedules created
     */
    protected function attemptRescheduleForMissingShifts(
        string $dateStr,
        array $unitsMissingMorning,
        array $unitsMissingAfternoon,
        array $scheduledDriverIds,
        array $driversOnLeaveIds,
        array &$dayDriversScheduled
    ): array {
        $result = [
            'morning_added' => 0,
            'afternoon_added' => 0
        ];
        
        $this->messages[] = "Attempting to find drivers for units with missing shifts on {$dateStr}";
        
        // Combine all scheduled drivers to ensure we don't double-assign
        $allScheduledDriverIdsSet = array_flip($scheduledDriverIds);
        foreach ($dayDriversScheduled as $driverId => $shift) {
            $allScheduledDriverIdsSet[$driverId] = true;
        }
        
        $driversOnLeaveIdsSet = array_flip($driversOnLeaveIds);
        
        // Try to find available drivers for morning shifts
        if (!empty($unitsMissingMorning)) {
            $morningSchedulesToCreate = [];
            
            foreach ($unitsMissingMorning as $unitId) {
                $unit = $this->units->firstWhere('id', $unitId);
                if (!$unit) {
                    continue;
                }
                
                // First try cadangan drivers who are not yet scheduled for this day
                $availableDrivers = $this->cadanganDrivers->filter(function($driver) use ($allScheduledDriverIdsSet, $driversOnLeaveIdsSet) {
                    return !isset($allScheduledDriverIdsSet[$driver->id]) && !isset($driversOnLeaveIdsSet[$driver->id]);
                });
                
                // Filter drivers to only those assigned to this specific unit
                $availableDrivers = $availableDrivers->filter(function($driver) use ($unitId) {
                    return $driver->units->contains('id', $unitId);
                });
                
                if ($availableDrivers->isEmpty()) {
                    // If no cadangan drivers available for this unit, try batangan drivers who are not yet scheduled
                    $availableDrivers = $this->batanganDrivers->filter(function($driver) use ($allScheduledDriverIdsSet, $driversOnLeaveIdsSet) {
                        return !isset($allScheduledDriverIdsSet[$driver->id]) && !isset($driversOnLeaveIdsSet[$driver->id]);
                    });
                    
                    // Filter batangan drivers to only those assigned to this specific unit
                    $availableDrivers = $availableDrivers->filter(function($driver) use ($unitId) {
                        return $driver->units->contains('id', $unitId);
                    });
                }
                
                if (!$availableDrivers->isEmpty()) {
                    // Find first available driver that can be assigned to this unit
                    // Driver MUST be assigned to this specific unit in the driver_units table
                    $driver = $availableDrivers->first(function($driver) use ($unit, $unitId) {
                        // Only check direct unit assignments through driver_units table
                        return $driver->units->contains('id', $unitId);
                    });
                    
                    if ($driver) {
                        $routeId = $unit->routes->isNotEmpty() ? $unit->routes->first()->id : null;
                        if ($routeId) {
                            $schedule = [
                                'unit_id' => $unit->id,
                                'driver_id' => $driver->id,
                                'route_id' => $routeId,
                                'schedule_date' => $dateStr,
                                'shift' => 'pagi',
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                            
                            $morningSchedulesToCreate[] = $schedule;
                            $allScheduledDriverIdsSet[$driver->id] = true;
                            $dayDriversScheduled[$driver->id] = 'pagi';
                            $this->messages[] = "Extra assignment: Driver {$driver->name} assigned to unit {$unit->unit_number} for morning shift on {$dateStr}";
                        }
                    }
                }
            }
            
            // Create the additional morning schedules if any were found
            if (!empty($morningSchedulesToCreate)) {
                list($success, $failed) = $this->utilityService->createSchedules($morningSchedulesToCreate, $this->messages);
                $this->success += $success;
                $this->failed += $failed;
                $result['morning_added'] = $success;
            }
        }
        
        // Try to find available drivers for afternoon shifts
        if (!empty($unitsMissingAfternoon)) {
            $afternoonSchedulesToCreate = [];
            
            foreach ($unitsMissingAfternoon as $unitId) {
                $unit = $this->units->firstWhere('id', $unitId);
                if (!$unit) {
                    continue;
                }
                
                // First try cadangan drivers who are not yet scheduled for this day
                $availableDrivers = $this->cadanganDrivers->filter(function($driver) use ($allScheduledDriverIdsSet, $driversOnLeaveIdsSet) {
                    return !isset($allScheduledDriverIdsSet[$driver->id]) && !isset($driversOnLeaveIdsSet[$driver->id]);
                });
                
                // Filter drivers to only those assigned to this specific unit
                $availableDrivers = $availableDrivers->filter(function($driver) use ($unitId) {
                    return $driver->units->contains('id', $unitId);
                });
                
                if ($availableDrivers->isEmpty()) {
                    // If no cadangan drivers available, try batangan drivers who are not yet scheduled
                    $availableDrivers = $this->batanganDrivers->filter(function($driver) use ($allScheduledDriverIdsSet, $driversOnLeaveIdsSet) {
                        return !isset($allScheduledDriverIdsSet[$driver->id]) && !isset($driversOnLeaveIdsSet[$driver->id]);
                    });
                    
                    // Filter batangan drivers to only those assigned to this specific unit
                    $availableDrivers = $availableDrivers->filter(function($driver) use ($unitId) {
                        return $driver->units->contains('id', $unitId);
                    });
                }
                
                if (!$availableDrivers->isEmpty()) {
                    // Find first available driver that can be assigned to this unit
                    // Driver MUST be assigned to this specific unit in the driver_units table
                    $driver = $availableDrivers->first(function($driver) use ($unit, $unitId) {
                        // Only check direct unit assignments through driver_units table
                        return $driver->units->contains('id', $unitId);
                    });
                    
                    if ($driver) {
                        $routeId = $unit->routes->isNotEmpty() ? $unit->routes->first()->id : null;
                        if ($routeId) {
                            $schedule = [
                                'unit_id' => $unit->id,
                                'driver_id' => $driver->id,
                                'route_id' => $routeId,
                                'schedule_date' => $dateStr,
                                'shift' => 'siang',
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                            
                            $afternoonSchedulesToCreate[] = $schedule;
                            $allScheduledDriverIdsSet[$driver->id] = true;
                            $dayDriversScheduled[$driver->id] = 'siang';
                            $this->messages[] = "Extra assignment: Driver {$driver->name} assigned to unit {$unit->unit_number} for afternoon shift on {$dateStr}";
                        }
                    }
                }
            }
            
            // Create the additional afternoon schedules if any were found
            if (!empty($afternoonSchedulesToCreate)) {
                list($success, $failed) = $this->utilityService->createSchedules($afternoonSchedulesToCreate, $this->messages);
                $this->success += $success;
                $this->failed += $failed;
                $result['afternoon_added'] = $success;
            }
        }
        
        return $result;
    }
}