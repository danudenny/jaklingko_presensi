<?php

namespace App\Services;

use App\Models\Driver;
use App\Models\DriverHistory;
use App\Models\DriverScheduleHistory;
use App\Models\DriverScheduleSettings;
use App\Models\Route;
use App\Models\Schedule;
use App\Models\Unit;
use App\Models\Holiday;
use App\Models\RenopsSettings;
use App\Models\UnitRenops;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for generating driver schedules with consideration for unit day offs
 *
 * This service handles the generation of driver schedules while respecting:
 * - Driver thresholds (min/max schedules per period)
 * - Unit day offs from unit_renops table
 * - Renops settings (resource allocation percentages)
 * - Shift sequence rules
 */
class ScheduleGeneratorService
{
    protected $routes;
    protected $units;
    protected $fixedDrivers;        // Regular drivers (D)
    protected $nonFixedDrivers;     // Non-fixed drivers (DD)
    protected $messages = [];
    protected $success = 0;
    protected $failed = 0;
    protected $periodDays = 15;     // 15-day scheduling period
    protected $weekendDriversPool = []; // Track drivers allocated for weekend shifts
    protected $holidaysInPeriod = []; // Cache holidays for the period
    protected $renopsSettings; // Renops settings from database
    protected $driverScheduleSettings = []; // Driver schedule settings by type
    protected $batanganSettings; // Batangan driver settings
    protected $cadanganSettings; // Cadangan driver settings
    protected $unitDayOffs = []; // Cache of unit day offs from unit_renops table
    protected $driverUnitMap = []; // Map of driver assignments to units for quick lookup
    protected $unitAssignmentsCache = []; // Cache of unit assignments for quick lookup

    /**
     * Generate schedules for a specific date range
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function generateSchedules(string $startDate, string $endDate): array
    {
        // Start measuring execution time
        $startTime = microtime(true);

        $startDate = Carbon::parse($startDate)->startOfDay();
        $endDate = Carbon::parse($endDate)->endOfDay();

        // Initialize results array
        $results = [
            'success' => 0,
            'failed' => 0,
            'messages' => []
        ];

        // Load all required data
        $this->loadData($results);

        // Debug the loaded data
        Log::info("=== DEBUG: Loaded data for schedule generation ===");
        Log::info("Routes: " . $this->routes->count());
        Log::info("Units: " . $this->units->count());
        Log::info("Batangan drivers: " . $this->fixedDrivers->count());
        Log::info("Cadangan drivers: " . $this->nonFixedDrivers->count());
        Log::info("Driver-Unit map entries: " . count($this->driverUnitMap));

        if ($this->units->isEmpty()) {
            $results['messages'][] = "No units available for scheduling. Cannot generate schedules.";
            return $results;
        }

        if ($this->fixedDrivers->isEmpty() && $this->nonFixedDrivers->isEmpty()) {
            $results['messages'][] = "No drivers available for scheduling. Cannot generate schedules.";
            return $results;
        }

        // Load renops settings
        $this->renopsSettings = RenopsSettings::getCurrentSettings();

        // Load driver schedule settings
        $this->loadDriverScheduleSettings();

        // Pre-load unit day offs from unit_renops for the period
        $this->preloadUnitDayOffs($startDate, $endDate);

        // Pre-cache data to avoid repeated queries
        $this->precacheUnitAssignments();
        $this->precacheRouteAssignments();
        $this->precacheExistingSchedules($startDate, $endDate);
        $this->precacheLeaveRequests($startDate, $endDate);

        // Process each day in the date range
        $currentDate = $startDate->copy();
        $totalDays = $startDate->diffInDays($endDate) + 1;
        $daysProcessed = 0;

        // Set a reasonable time limit for the operation
        set_time_limit(300); // 5 minutes

        $this->messages[] = "Starting schedule generation for {$totalDays} days from {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}";

        // Pre-load all unit day offs for the entire period at once to avoid repeated queries
        $allUnitDayOffs = UnitRenops::whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get()
            ->groupBy(function ($item) {
                return $item->date->format('Y-m-d');
            });

        foreach ($allUnitDayOffs as $date => $entries) {
            $this->unitDayOffs[$date] = $entries->pluck('unit_id')->toArray();
        }

        $this->messages[] = "Preloaded day offs for all dates in the period";

        // Process in smaller chunks to avoid timeouts
        $chunkSize = 5; // Process 5 days at a time
        $chunks = ceil($totalDays / $chunkSize);

        // Pre-load all existing schedules for the entire period
        $existingSchedules = Schedule::whereBetween('schedule_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get()
            ->groupBy('schedule_date');

        // Pre-load all leave requests for the entire period
        $leaveRequests = DB::table('leave_requests')
            ->where('status', 'approved')
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                    ->orWhereBetween('end_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        $q->where('start_date', '<=', $startDate->format('Y-m-d'))
                          ->where('end_date', '>=', $endDate->format('Y-m-d'));
                    });
            })
            ->get();

        // Create a lookup array for drivers on leave by date
        $driversOnLeaveByDate = [];
        foreach ($leaveRequests as $leave) {
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

                // Get unavailable units for this day from unit_renops table
                $unavailableUnitIds = $this->getUnavailableUnitsForDay($dateStr);

                // Determine resource percentage based on day type (still used for weekend driver pools)
                $resourcePercentage = $this->getResourcePercentageFromUnitRenops($dateStr);

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
                $existingSchedulesForDate = $existingSchedules->get($dateStr, collect());
                $scheduledUnitIdsMorning = $existingSchedulesForDate->where('shift', 'pagi')->pluck('unit_id')->toArray();
                $scheduledUnitIdsEvening = $existingSchedulesForDate->where('shift', 'siang')->pluck('unit_id')->toArray();
                $scheduledDriverIds = $existingSchedulesForDate->pluck('driver_id')->toArray();

                // Get drivers on leave for this date
                $driversOnLeaveIds = $driversOnLeaveByDate[$dateStr] ?? [];

                // Process morning shift with optimized data
                $this->generateShiftSchedulesOptimized(
                    $dateStr,
                    'pagi',
                    $resourcePercentage,
                    $startDate->format('Y-m-d'),
                    $endDate->format('Y-m-d'),
                    $unavailableUnitIds,
                    $scheduledUnitIdsMorning,
                    $scheduledDriverIds,
                    $driversOnLeaveIds
                );

                // Process evening shift with optimized data
                $this->generateShiftSchedulesOptimized(
                    $dateStr,
                    'siang',
                    $resourcePercentage,
                    $startDate->format('Y-m-d'),
                    $endDate->format('Y-m-d'),
                    $unavailableUnitIds,
                    $scheduledUnitIdsEvening,
                    $scheduledDriverIds,
                    $driversOnLeaveIds
                );

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
        $results['execution_time'] = $executionTime;

        return $results;
    }

    /**
     * Optimized version of generateShiftSchedules that uses pre-cached data
     */
    protected function generateShiftSchedulesOptimized(
        string $date,
        string $shift,
        int $resourcePercentage,
        string $periodStartDate,
        string $periodEndDate,
        array $unavailableUnitIds,
        array $scheduledUnitIds,
        array $scheduledDriverIds,
        array $driversOnLeaveIds
    ): void {
        $carbonDate = Carbon::parse($date);

        $this->messages[] = "Found " . count($scheduledUnitIds) . " existing schedules for $date ($shift)";
        $this->messages[] = "Processing " . count($unavailableUnitIds) . " day offs from unit_renops for $date";

        // Add debug logging
        Log::info("=== DEBUG: Starting schedule generation for $date ($shift) ===");
        Log::info("Resource percentage: $resourcePercentage%");
        Log::info("Unavailable units: " . count($unavailableUnitIds));
        Log::info("Already scheduled units: " . count($scheduledUnitIds));
        Log::info("Already scheduled drivers: " . count($scheduledDriverIds));
        Log::info("Drivers on leave: " . count($driversOnLeaveIds));

        // Combine unavailable units and already scheduled units into one array for faster lookups
        $unavailableAndScheduledUnitIds = array_merge($unavailableUnitIds, $scheduledUnitIds);
        $unavailableAndScheduledUnitIdsSet = array_flip($unavailableAndScheduledUnitIds); // Convert to hash map for O(1) lookups

        // Filter units that are available (not in unavailable list) and don't have schedules yet
        // Using array operations for better performance
        $unitsToSchedule = $this->units->filter(function ($unit) use ($unavailableAndScheduledUnitIdsSet) {
            // Unit is available if it's not in the unavailable list and doesn't have a schedule yet
            return !isset($unavailableAndScheduledUnitIdsSet[$unit->id]);
        });

        $this->messages[] = "For $date ($shift), after filtering, {$unitsToSchedule->count()} units are available for scheduling.";
        Log::info("Units available for scheduling: " . $unitsToSchedule->count());
        
        // Debug: Check if units have route_id
        $unitsWithoutRoute = $unitsToSchedule->filter(function($unit) {
            return empty($unit->route_id);
        })->count();
        
        Log::info("Units without route_id: $unitsWithoutRoute");
        
        // If no units to schedule, return early
        if ($unitsToSchedule->isEmpty()) {
            $this->messages[] = "No units available for scheduling on $date ($shift). Skipping.";
            Log::info("No units available for scheduling on $date ($shift). Skipping.");
            return;
        }

        // Get previous day schedules for shift sequence rules
        $previousDayDateStr = $carbonDate->copy()->subDay()->format('Y-m-d');
        $previousDaySchedules = Schedule::where('schedule_date', $previousDayDateStr)
            ->get()
            ->keyBy('driver_id');

        // Get two days ago schedules if needed for shift sequence rules
        $twoDaysAgoDateStr = $carbonDate->copy()->subDays(2)->format('Y-m-d');
        $twoDaysAgoSchedules = Schedule::where('schedule_date', $twoDaysAgoDateStr)
            ->get()
            ->keyBy('driver_id');

        // Get driver schedule counts for this period (one query instead of many)
        $driverScheduleCounts = [];
        $scheduleCountsQuery = Schedule::whereBetween('schedule_date', [$periodStartDate, $periodEndDate])
            ->select('driver_id', DB::raw('count(*) as schedule_count'))
            ->groupBy('driver_id');

        foreach ($scheduleCountsQuery->get() as $record) {
            $driverScheduleCounts[$record->driver_id] = $record->schedule_count;
        }

        // Prepare a batch of schedules to insert
        $schedulesToCreate = [];
        $successCount = 0;
        $failedCount = 0;

        // Create a set of scheduled driver IDs for fast lookup
        $scheduledDriverIdsSet = array_flip($scheduledDriverIds);
        $driversOnLeaveIdsSet = array_flip($driversOnLeaveIds);

        // Get batangan and cadangan settings
        $batanganSettings = $this->driverScheduleSettings['batangan'];
        $cadanganSettings = $this->driverScheduleSettings['cadangan'];

        // Debug log for driver JM (ID 355)
        $jmScheduled = isset($scheduledDriverIdsSet[355]);
        $jmOnLeave = isset($driversOnLeaveIdsSet[355]);
        Log::info("Before scheduling - Driver JM (ID 355): Already scheduled: " . ($jmScheduled ? 'Yes' : 'No') . ", On leave: " . ($jmOnLeave ? 'Yes' : 'No'));

        // Track which units driver JM is assigned to during this scheduling run
        $jmAssignments = [];

        foreach ($unitsToSchedule as $unit) {
            // Ensure unit has at least one route assigned, critical for driver assignment logic
            if (!$unit->routes || $unit->routes->isEmpty()) {
                $this->messages[] = "Unit {$unit->unit_number} (ID: {$unit->id}) has no routes assigned. Skipping for $date ($shift).";
                $failedCount++;
                continue;
            }

            // Get the first route for this unit (we'll use this for scheduling)
            $routeIdToAssign = $unit->routes->first()->id;
            
            // Find suitable driver for this unit using optimized method
            $driver = $this->findSuitableDriverForUnitOptimized(
                $unit,
                $date,
                $shift,
                $scheduledDriverIdsSet,
                $driversOnLeaveIdsSet,
                $previousDaySchedules->toArray(), // Convert Collection to array
                $twoDaysAgoSchedules->toArray(), // Convert Collection to array
                $driverScheduleCounts
            );

            if ($driver) {
                try {
                    $schedulesToCreate[] = [
                        'schedule_date' => $date,
                        'shift' => $shift,
                        'unit_id' => $unit->id,
                        'driver_id' => $driver->id,
                        'route_id' => $routeIdToAssign,
                        'status' => 'scheduled',
                        'created_at' => now(),
                        'updated_at' => now()
                    ];

                    // Track if driver JM is assigned
                    if ($driver->id == 355) {
                        $jmAssignments[] = "Unit ID: {$unit->id}, Unit Number: {$unit->unit_number}, Date: {$date}, Shift: {$shift}";
                        Log::info("Driver JM (ID 355) assigned to Unit ID {$unit->id} ({$unit->unit_number}) for {$date} ({$shift})");
                    }

                    // Add this driver to the scheduled drivers set to avoid double-booking
                    $scheduledDriverIdsSet[$driver->id] = 1;

                    $successCount++;

                    // Log every 10 successful assignments to reduce memory usage
                    if ($successCount % 10 === 0) {
                        $this->messages[] = "Created {$successCount} schedules for $date ($shift) so far.";
                    }

                } catch (\Exception $e) {
                    $failedCount++;
                    $this->messages[] = "ERROR preparing schedule for Unit ID {$unit->id} with Driver ID {$driver->id} for $date ($shift): " . $e->getMessage();
                    Log::error("Schedule preparation error: " . $e->getMessage(), ['unit_id' => $unit->id, 'driver_id' => $driver->id, 'date' => $date, 'shift' => $shift]);
                }
            } else {
                $failedCount++;
                // Only log detailed failures for a limited number to avoid excessive memory usage
                if ($failedCount <= 10) {
                    $this->messages[] = "FAILED_ASSIGN: No suitable driver found for Unit ID {$unit->id} ({$unit->unit_number}) on Route ID {$unit->route_id} for $date ($shift).";
                    Log::info("FAILED_ASSIGN: No suitable driver found for Unit ID {$unit->id} ({$unit->unit_number}) on Route ID {$unit->route_id} for $date ($shift).");
                }
            }
        }

        // Log all JM assignments for this scheduling run
        if (!empty($jmAssignments)) {
            Log::info("Driver JM (ID 355) assignments for {$date} ({$shift}):", $jmAssignments);
        } else {
            Log::info("Driver JM (ID 355) was not assigned to any unit for {$date} ({$shift})");
        }

        // Insert schedules in chunks to improve performance
        if (!empty($schedulesToCreate)) {
            $chunkSize = 50; // Insert 50 records at a time
            $chunks = array_chunk($schedulesToCreate, $chunkSize);

            foreach ($chunks as $chunk) {
                try {
                    Schedule::insert($chunk);
                } catch (\Exception $e) {
                    $this->messages[] = "ERROR inserting schedules: " . $e->getMessage();
                    Log::error("Error inserting schedules: " . $e->getMessage());
                }
            }

            $this->messages[] = "Successfully created {$successCount} schedules for $date ($shift).";
            Log::info("Successfully created {$successCount} schedules for $date ($shift).");
        } else {
            $this->messages[] = "No schedules were created for $date ($shift).";
            Log::info("No schedules were created for $date ($shift).");
        }

        if ($failedCount > 0) {
            $this->messages[] = "Failed to create {$failedCount} schedules for $date ($shift).";
            Log::info("Failed to create {$failedCount} schedules for $date ($shift).");
        }
    }

    /**
     * Find a suitable driver for a unit on a specific date and shift
     * This optimized version uses pre-cached data and prioritizes drivers with fewer schedules
     *
     * @param Unit $unit
     * @param string $dateStr
     * @param string $shift
     * @param array $scheduledDriverIdsSet
     * @param array $driversOnLeaveIdsSet
     * @param array $previousDaySchedules
     * @param array $twoDaysAgoSchedules
     * @param array $driverScheduleCounts
     * @return Driver|null
     */
    protected function findSuitableDriverForUnitOptimized(
        Unit $unit,
        string $dateStr,
        string $shift,
        array $scheduledDriverIdsSet,
        array $driversOnLeaveIdsSet,
        array $previousDaySchedules,
        array $twoDaysAgoSchedules,
        array $driverScheduleCounts
    ): ?Driver {
        // Get the route for this unit
        if ($unit->routes->isEmpty()) {
            $this->messages[] = "Unit {$unit->name} has no routes assigned, skipping.";
            return null;
        }

        $routeId = $unit->routes->first()->id;

        // Debug info for specific unit
        if ($unit->id == 466) {
            Log::info("Processing unit ID 466 for date {$dateStr} shift {$shift}");
            Log::info("Route ID for this unit: {$routeId}");
        }

        // First priority: Try to find a batangan driver specifically assigned to this unit
        $unitBatanganDrivers = $this->fixedDrivers->filter(function($driver) use ($unit) {
            return isset($this->unitAssignmentsCache[$unit->id]) && 
                   isset($this->unitAssignmentsCache[$unit->id][$driver->id]);
        });

        if ($unit->id == 466) {
            Log::info("Found " . $unitBatanganDrivers->count() . " batangan drivers assigned to unit 466");
        }

        if (!$unitBatanganDrivers->isEmpty()) {
            $driver = $this->filterAndFindSuitableDriverOptimized(
                $unitBatanganDrivers,
                $unit,
                $dateStr,
                $shift,
                $scheduledDriverIdsSet,
                $driversOnLeaveIdsSet,
                $previousDaySchedules,
                $twoDaysAgoSchedules,
                $driverScheduleCounts,
                $this->batanganSettings,
                $this->cadanganSettings,
                true // Strict unit assignment for batangan drivers
            );

            if ($driver) {
                $this->messages[] = "Assigned batangan driver {$driver->name} to unit {$unit->name} for {$dateStr} {$shift} (unit-specific assignment)";
                return $driver;
            }
        }

        // Second priority: Try to find a cadangan driver specifically assigned to this unit
        $unitCadanganDrivers = $this->nonFixedDrivers->filter(function($driver) use ($unit) {
            return isset($this->unitAssignmentsCache[$unit->id]) && 
                   isset($this->unitAssignmentsCache[$unit->id][$driver->id]);
        });

        if ($unit->id == 466) {
            Log::info("Found " . $unitCadanganDrivers->count() . " cadangan drivers assigned to unit 466");
        }

        if (!$unitCadanganDrivers->isEmpty()) {
            $driver = $this->filterAndFindSuitableDriverOptimized(
                $unitCadanganDrivers,
                $unit,
                $dateStr,
                $shift,
                $scheduledDriverIdsSet,
                $driversOnLeaveIdsSet,
                $previousDaySchedules,
                $twoDaysAgoSchedules,
                $driverScheduleCounts,
                $this->cadanganSettings,
                $this->batanganSettings,
                false // No strict unit assignment for cadangan drivers
            );

            if ($driver) {
                $this->messages[] = "Assigned cadangan driver {$driver->name} to unit {$unit->name} for {$dateStr} {$shift} (unit-specific assignment)";
                return $driver;
            }
        }

        // Third priority: Try to find any cadangan driver qualified for this route
        $routeQualifiedDrivers = $this->nonFixedDrivers->filter(function($driver) use ($routeId) {
            return isset($this->routeAssignmentsCache[$routeId]) && 
                   isset($this->routeAssignmentsCache[$routeId][$driver->id]);
        });

        if ($unit->id == 466) {
            Log::info("Found " . $routeQualifiedDrivers->count() . " cadangan drivers qualified for route {$routeId}");
        }

        if (!$routeQualifiedDrivers->isEmpty()) {
            $driver = $this->filterAndFindSuitableDriverOptimized(
                $routeQualifiedDrivers,
                $unit,
                $dateStr,
                $shift,
                $scheduledDriverIdsSet,
                $driversOnLeaveIdsSet,
                $previousDaySchedules,
                $twoDaysAgoSchedules,
                $driverScheduleCounts,
                $this->cadanganSettings,
                $this->batanganSettings,
                false // No strict unit assignment for cadangan drivers
            );

            if ($driver) {
                $this->messages[] = "Assigned cadangan driver {$driver->name} to unit {$unit->name} for {$dateStr} {$shift} (route-qualified)";
                return $driver;
            }
        }

        // No suitable driver found
        $this->messages[] = "No suitable driver found for unit {$unit->name} for {$dateStr} {$shift}";
        return null;
    }

    /**
     * Optimized version of filterAndFindSuitableDriver that uses pre-cached data
     */
    protected function filterAndFindSuitableDriverOptimized(
        $drivers,
        $unit,
        $date,
        $shift,
        $scheduledDriverIdsSet,
        $driversOnLeaveIdsSet,
        $previousDaySchedules,
        $twoDaysAgoSchedules,
        $driverScheduleCounts,
        $driverSettings,
        $otherTypeSettings,
        $strictUnitAssignment = false
    ): ?Driver {
        // Filter out drivers that are already scheduled for this day
        $availableDrivers = $drivers->filter(function ($driver) use ($scheduledDriverIdsSet) {
            return !isset($scheduledDriverIdsSet[$driver->id]);
        });

        if ($availableDrivers->isEmpty()) {
            return null;
        }

        // Filter out drivers that are on leave
        $availableDrivers = $availableDrivers->filter(function ($driver) use ($driversOnLeaveIdsSet) {
            return !isset($driversOnLeaveIdsSet[$driver->id]);
        });

        if ($availableDrivers->isEmpty()) {
            return null;
        }

        // Filter out drivers that have reached their maximum schedules for the period
        $availableDrivers = $availableDrivers->filter(function ($driver) use ($driverScheduleCounts, $driverSettings) {
            $currentCount = $driverScheduleCounts[$driver->id] ?? 0;
            return $currentCount < $driverSettings['max_schedules'];
        });

        if ($availableDrivers->isEmpty()) {
            return null;
        }

        // Filter out drivers that have shifts that would violate shift sequence rules
        $availableDrivers = $availableDrivers->filter(function ($driver) use ($previousDaySchedules, $twoDaysAgoSchedules, $shift) {
            // Check if driver had a schedule yesterday
            $hadYesterdaySchedule = isset($previousDaySchedules[$driver->id]);
            
            // Check if driver had a schedule two days ago
            $hadTwoDaysAgoSchedule = isset($twoDaysAgoSchedules[$driver->id]);
            
            // If driver had a schedule yesterday with the same shift, don't assign them again
            if ($hadYesterdaySchedule && $previousDaySchedules[$driver->id]['shift'] === $shift) {
                return false;
            }
            
            // If driver had schedules both yesterday and two days ago, give them a break
            if ($hadYesterdaySchedule && $hadTwoDaysAgoSchedule) {
                return false;
            }
            
            return true;
        });

        if ($availableDrivers->isEmpty()) {
            return null;
        }

        // Sort drivers by the number of schedules they have in the current period
        // This ensures fair distribution of schedules among drivers
        $sortedDrivers = $availableDrivers->sortBy(function ($driver) use ($driverScheduleCounts, $unit) {
            // Get the current count of schedules for this driver
            $currentCount = $driverScheduleCounts[$driver->id] ?? 0;
            
            // For unit-specific sorting, check if this driver has been assigned to this specific unit before
            $unitSpecificCount = Schedule::where('driver_id', $driver->id)
                ->where('unit_id', $unit->id)
                ->count();
                
            // Prioritize drivers with fewer schedules for this specific unit
            // This creates a rotation system for drivers assigned to the same unit
            return [$unitSpecificCount, $currentCount];
        });

        // Return the first available driver after all filtering and sorting
        return $sortedDrivers->first();
    }

    /**
     * Balance schedules in the period to ensure all drivers meet their minimum requirements
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param array $results
     * @return void
     */
    protected function balanceSchedulesInPeriod(Carbon $startDate, Carbon $endDate, array &$results): void
    {
        $this->messages[] = "Starting schedule balancing to ensure minimum requirements are met...";

        // Get all schedules for the period
        $periodSchedules = Schedule::whereBetween('schedule_date', [
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d')
            ])
            ->get();

        // Count schedules per driver
        $driverScheduleCounts = [];
        foreach ($periodSchedules as $schedule) {
            if (!isset($driverScheduleCounts[$schedule->driver_id])) {
                $driverScheduleCounts[$schedule->driver_id] = 0;
            }
            $driverScheduleCounts[$schedule->driver_id]++;
        }

        // Get all active drivers
        $allDrivers = Driver::active()->get();

        // Check which drivers don't meet minimum requirements
        $underScheduledDrivers = [];
        $overScheduledDrivers = [];

        foreach ($allDrivers as $driver) {
            $count = $driverScheduleCounts[$driver->id] ?? 0;
            $settings = $this->driverScheduleSettings[$driver->type];

            if ($count < $settings['min_schedules']) {
                // Driver doesn't meet minimum schedules
                $underScheduledDrivers[] = [
                    'driver' => $driver,
                    'current' => $count,
                    'needed' => $settings['min_schedules'] - $count
                ];
            } elseif ($count > $settings['max_schedules']) {
                // Driver exceeds maximum schedules
                $overScheduledDrivers[] = [
                    'driver' => $driver,
                    'current' => $count,
                    'excess' => $count - $settings['max_schedules']
                ];
            }
        }

        // Log the findings
        $this->messages[] = "Found " . count($underScheduledDrivers) . " drivers below minimum schedule requirements";
        $this->messages[] = "Found " . count($overScheduledDrivers) . " drivers above maximum schedule requirements";

        // If we have under-scheduled drivers, try to assign them to available slots
        if (!empty($underScheduledDrivers)) {
            // Sort by most needed schedules first
            usort($underScheduledDrivers, function($a, $b) {
                return $b['needed'] - $a['needed'];
            });

            $this->assignMissingSchedules($underScheduledDrivers, $startDate, $endDate, $results);
        }
    }

    /**
     * Try to assign schedules to under-scheduled drivers
     *
     * @param array $underScheduledDrivers
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param array $results
     * @return void
     */
    protected function assignMissingSchedules(array $underScheduledDrivers, Carbon $startDate, Carbon $endDate, array &$results): void
    {
        // For each under-scheduled driver, try to find days where they can be scheduled
        foreach ($underScheduledDrivers as $driverData) {
            $driver = $driverData['driver'];
            $needed = $driverData['needed'];

            $this->messages[] = "Driver {$driver->name} (ID: {$driver->id}) needs {$needed} more schedules to meet minimum requirements";

            // Get dates in the period
            $currentDate = $startDate->copy();
            $endDateStr = $endDate->format('Y-m-d');
            $assignedCount = 0;

            // Get routes this driver can drive
            $driverRouteIds = $driver->routes()->pluck('routes.id')->toArray();

            // Find days where this driver can be scheduled
            while ($currentDate->format('Y-m-d') <= $endDateStr && $assignedCount < $needed) {
                $dateStr = $currentDate->format('Y-m-d');

                // Check if driver already has a schedule on this date
                $existingSchedule = Schedule::where('schedule_date', $dateStr)
                    ->where('driver_id', $driver->id)
                    ->first();

                if (!$existingSchedule) {
                    // Check if driver is on leave
                    $isOnLeave = DB::table('leave_requests')
                        ->where('driver_id', $driver->id)
                        ->where('status', 'approved')
                        ->where('start_date', '<=', $dateStr)
                        ->where('end_date', '>=', $dateStr)
                        ->exists();

                    if (!$isOnLeave) {
                        // Try to find an unassigned unit for this date that matches driver's routes
                        foreach (['pagi', 'siang'] as $shift) {
                            // Skip if we've already assigned enough schedules
                            if ($assignedCount >= $needed) {
                                break;
                            }

                            // Find units without drivers for this date/shift that match driver's routes
                            $availableUnits = Unit::whereHas('routes', function ($query) use ($driverRouteIds) {
                                    $query->whereIn('routes.id', $driverRouteIds);
                                })
                                ->whereDoesntHave('schedules', function ($query) use ($dateStr, $shift) {
                                    $query->where('schedule_date', $dateStr)
                                        ->where('shift', $shift);
                                })
                                ->whereNotIn('id', function ($query) use ($dateStr) {
                                    $query->select('unit_id')
                                        ->from('unit_renops')
                                        ->where('date', $dateStr);
                                })
                                ->get();

                            if ($availableUnits->isNotEmpty()) {
                                // Take the first available unit
                                $unit = $availableUnits->first();

                                // Get the first route for this unit
                                $unitRoute = $unit->routes()->first();

                                if (!$unitRoute) {
                                    $this->messages[] = "Warning: Unit {$unit->unit_number} has no assigned routes. Skipping assignment.";
                                    continue;
                                }

                                try {
                                    // Create a schedule for this driver
                                    Schedule::create([
                                        'schedule_date' => $dateStr,
                                        'shift' => $shift,
                                        'unit_id' => $unit->id,
                                        'driver_id' => $driver->id,
                                        'route_id' => $unitRoute->id,
                                        'status' => 'scheduled'
                                    ]);

                                    $assignedCount++;
                                    $this->success++;

                                    $this->messages[] = "Assigned driver {$driver->name} to unit {$unit->unit_number} on {$dateStr} ({$shift}) during balancing";

                                    // If this is a batangan driver, we should only assign them to their specific unit/route
                                    if ($driver->type === 'batangan') {
                                        break; // Only one shift per day for batangan drivers
                                    }
                                } catch (\Exception $e) {
                                    $this->messages[] = "Error assigning driver {$driver->name} during balancing: " . $e->getMessage();
                                }
                            }
                        }
                    }
                }

                $currentDate->addDay();
            }

            $this->messages[] = "Added {$assignedCount} schedules for driver {$driver->name} during balancing";
        }
    }

    /**
     * Load all required data for scheduling
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
            $this->fixedDrivers = collect();
            $this->nonFixedDrivers = collect();
            
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
            $this->fixedDrivers = $allDrivers->where('type', 'batangan');
            $this->nonFixedDrivers = $allDrivers->where('type', 'cadangan');

            $results['messages'][] = "Split drivers: " . $this->fixedDrivers->count() . " batangan, " . $this->nonFixedDrivers->count() . " cadangan";

            // Create a map of driver assignments to units for quick lookup
            $this->driverUnitMap = [];
            
            // First try to get assignments from the driver's loaded relationships
            foreach ($allDrivers as $driver) {
                if ($driver->units && $driver->units->isNotEmpty()) {
                    $this->driverUnitMap[$driver->id] = $driver->units->pluck('id')->toArray();
                }
            }
            
            // If the map is empty or has very few entries, try loading directly from the database
            if (count($this->driverUnitMap) < 10) {
                Log::warning("Few driver-unit assignments found in relationships. Loading from database directly.");
                
                $driverUnits = DB::table('driver_units')->get();
                foreach ($driverUnits as $assignment) {
                    if (!isset($this->driverUnitMap[$assignment->driver_id])) {
                        $this->driverUnitMap[$assignment->driver_id] = [];
                    }
                    $this->driverUnitMap[$assignment->driver_id][] = $assignment->unit_id;
                }
            }
            
            $results['messages'][] = "Created driver-unit assignment map for " . count($this->driverUnitMap) . " drivers";
            
            // Check specific driver JM (ID 355)
            if (isset($this->driverUnitMap[355])) {
                $results['messages'][] = "Driver JM (ID 355) is assigned to units: " . implode(', ', $this->driverUnitMap[355]);
            } else {
                $results['messages'][] = "Driver JM (ID 355) has no unit assignments in driver_units table";
            }
            
            // Debug: Check if any batangan drivers have unit assignments
            $batanganWithAssignments = 0;
            foreach ($this->fixedDrivers as $driver) {
                if (isset($this->driverUnitMap[$driver->id])) {
                    $batanganWithAssignments++;
                }
            }
            Log::info("Batangan drivers with unit assignments: $batanganWithAssignments out of " . $this->fixedDrivers->count());
            
            // Debug: Check if any cadangan drivers have unit assignments
            $cadanganWithAssignments = 0;
            foreach ($this->nonFixedDrivers as $driver) {
                if (isset($this->driverUnitMap[$driver->id])) {
                    $cadanganWithAssignments++;
                }
            }
            Log::info("Cadangan drivers with unit assignments: $cadanganWithAssignments out of " . $this->nonFixedDrivers->count());
            
        } catch (\Exception $e) {
            $results['messages'][] = "ERROR loading data: " . $e->getMessage();
            Log::error("Error loading data for schedule generation: " . $e->getMessage());
        }
    }

    /**
     * Load driver schedule settings from database
     *
     * @return void
     */
    protected function loadDriverScheduleSettings(): void
    {
        // Get settings for batangan (fixed) drivers
        $batanganSettings = DriverScheduleSettings::getSettingsForType('batangan');
        $this->driverScheduleSettings['batangan'] = [
            'min_schedules' => $batanganSettings->min_schedules,
            'max_schedules' => $batanganSettings->max_schedules,
            'period_days' => $batanganSettings->period_days
        ];
        
        // Set the class property for direct access
        $this->batanganSettings = $this->driverScheduleSettings['batangan'];

        // Get settings for cadangan (non-fixed) drivers
        $cadanganSettings = DriverScheduleSettings::getSettingsForType('cadangan');
        $this->driverScheduleSettings['cadangan'] = [
            'min_schedules' => $cadanganSettings->min_schedules,
            'max_schedules' => $cadanganSettings->max_schedules,
            'period_days' => $cadanganSettings->period_days
        ];
        
        // Set the class property for direct access
        $this->cadanganSettings = $this->driverScheduleSettings['cadangan'];

        $this->messages[] = "Loaded driver schedule settings - Batangan: min {$batanganSettings->min_schedules}, max {$batanganSettings->max_schedules}; " .
                           "Cadangan: min {$cadanganSettings->min_schedules}, max {$cadanganSettings->max_schedules}";
    }

    /**
     * Preload unit day offs from unit_renops table for the entire period
     * This improves performance by avoiding repeated database queries
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return void
     */
    protected function preloadUnitDayOffs(Carbon $startDate, Carbon $endDate): void
    {
        // Get all unit day offs for the period
        $unitRenops = UnitRenops::whereBetween('date', [
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d')
            ])
            ->get();

        // Group by date for faster lookups
        $this->unitDayOffs = $unitRenops->groupBy('date')
            ->map(function ($dateGroup) {
                return $dateGroup->pluck('unit_id')->toArray();
            })
            ->all();

        // Also store day types from unit_renops for resource percentage calculations
        $this->dayTypes = $unitRenops->groupBy('date')
            ->map(function ($dateGroup) {
                // Get the first day_type for this date (should be consistent)
                return $dateGroup->first()->day_type;
            })
            ->all();

        $this->messages[] = "Preloaded unit day offs for " . count($this->unitDayOffs) . " days";
        $this->messages[] = "Preloaded day types for " . count($this->dayTypes) . " days";
    }

    /**
     * Get unavailable units for a specific day from unit_renops table
     *
     * @param string $dateStr The date in Y-m-d format
     * @return array Array of unit IDs that are unavailable on this date
     */
    protected function getUnavailableUnitsForDay(string $dateStr): array
    {
        // Use the preloaded data if available
        if (isset($this->unitDayOffs[$dateStr])) {
            return $this->unitDayOffs[$dateStr];
        }

        // Fallback to database query if not preloaded
        return UnitRenops::where('date', $dateStr)
            ->pluck('unit_id')
            ->toArray();
    }

    /**
     * Get resource percentage based on day type from unit_renops table
     * 
     * @param string $dateStr The date in Y-m-d format
     * @return int Resource percentage (0-100)
     */
    protected function getResourcePercentageFromUnitRenops(string $dateStr): int
    {
        // Use the preloaded day types if available
        if (isset($this->dayTypes[$dateStr])) {
            $dayType = $this->dayTypes[$dateStr];
            
            // Determine resource percentage based on day type
            switch ($dayType) {
                case 'holiday':
                    $threshold = $this->renopsSettings->holiday_threshold;
                    $this->messages[] = "Date {$dateStr} is a holiday, using {$threshold}% resource allocation";
                    return (int)$threshold;
                    
                case 'saturday':
                    $threshold = $this->renopsSettings->saturday_threshold;
                    $this->messages[] = "Date {$dateStr} is a Saturday, using {$threshold}% resource allocation";
                    return (int)$threshold;
                    
                case 'sunday':
                    $threshold = $this->renopsSettings->sunday_threshold;
                    $this->messages[] = "Date {$dateStr} is a Sunday, using {$threshold}% resource allocation";
                    return (int)$threshold;
                    
                default:
                    $this->messages[] = "Date {$dateStr} is a weekday, using 100% resource allocation";
                    return 100; // 100% for weekdays
            }
        }
        
        // Fallback to database query if not preloaded
        $unitRenops = UnitRenops::where('date', $dateStr)->first();
        
        if ($unitRenops) {
            $dayType = $unitRenops->day_type;
            
            // Determine resource percentage based on day type
            switch ($dayType) {
                case 'holiday':
                    $threshold = $this->renopsSettings->holiday_threshold;
                    $this->messages[] = "Date {$dateStr} is a holiday, using {$threshold}% resource allocation";
                    return (int)$threshold;
                    
                case 'saturday':
                    $threshold = $this->renopsSettings->saturday_threshold;
                    $this->messages[] = "Date {$dateStr} is a Saturday, using {$threshold}% resource allocation";
                    return (int)$threshold;
                    
                case 'sunday':
                    $threshold = $this->renopsSettings->sunday_threshold;
                    $this->messages[] = "Date {$dateStr} is a Sunday, using {$threshold}% resource allocation";
                    return (int)$threshold;
            }
        }
        
        // Default to 100% if no day type information is found
        $this->messages[] = "No day type information found for {$dateStr}, using 100% resource allocation";
        return 100;
    }

    /**
     * Pre-cache unit assignments for all units to avoid repeated queries
     *
     * @return void
     */
    protected function precacheUnitAssignments(): void
    {
        // Create a log entry to track this operation
        Log::info("Precaching unit assignments for drivers");

        // Get all driver-unit assignments
        $driverUnits = DB::table('driver_units')->get();

        // Log the total count of assignments
        Log::info("Found " . $driverUnits->count() . " driver-unit assignments");

        // For specific driver (JM - ID 355), log their unit assignments
        $jmUnits = $driverUnits->where('driver_id', 355)->pluck('unit_id')->toArray();
        if (!empty($jmUnits)) {
            Log::info("Driver JM (ID 355) is assigned to units: " . implode(', ', $jmUnits));
        } else {
            Log::info("Driver JM (ID 355) has no unit assignments in driver_units table");
        }
        
        // Recreate the unit assignments cache that was removed in the previous edit
        $this->unitAssignmentsCache = DB::table('driver_units')
            ->select('driver_id', 'unit_id')
            ->get()
            ->groupBy('unit_id')
            ->map(function ($items) {
                return $items->pluck('driver_id')->flip()->all();
            })
            ->all();
            
        Log::info("Unit assignments cache populated with " . count($this->unitAssignmentsCache) . " entries");
    }

    /**
     * Pre-cache route assignments for all routes to avoid repeated queries
     *
     * @return void
     */
    protected function precacheRouteAssignments(): void
    {
        // This method pre-caches all driver-route assignments to avoid repeated database queries
        $this->routeAssignmentsCache = DB::table('driver_routes')
            ->select('driver_id', 'route_id')
            ->get()
            ->groupBy('route_id')
            ->map(function ($items) {
                return $items->pluck('driver_id')->flip()->all();
            })
            ->all();
    }

    /**
     * Pre-cache existing schedules for the entire period
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return void
     */
    protected function precacheExistingSchedules(Carbon $startDate, Carbon $endDate): void
    {
        // This method pre-caches all existing schedules to avoid repeated database queries
        $this->existingSchedulesCache = Schedule::whereBetween('schedule_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get()
            ->groupBy('schedule_date')
            ->map(function ($daySchedules) {
                return $daySchedules->keyBy('driver_id');
            })
            ->all();
    }

    /**
     * Pre-cache leave requests for the entire period
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return void
     */
    protected function precacheLeaveRequests(Carbon $startDate, Carbon $endDate): void
    {
        // This method pre-caches all approved leave requests to avoid repeated database queries
        $this->leaveRequestsCache = DB::table('leave_requests')
            ->where('status', 'approved')
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                    ->orWhereBetween('end_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        $q->where('start_date', '<=', $startDate->format('Y-m-d'))
                          ->where('end_date', '>=', $endDate->format('Y-m-d'));
                    });
            })
            ->get()
            ->groupBy(function ($item) {
                // Create a date range for each leave request
                $start = Carbon::parse($item->start_date);
                $end = Carbon::parse($item->end_date);
                $dates = [];

                for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                    $dates[] = $date->format('Y-m-d');
                }

                return $item->driver_id . '|' . implode(',', $dates);
            })
            ->map(function ($items, $key) {
                // Extract driver_id and dates from the key
                list($driverId, $datesStr) = explode('|', $key);
                $dates = explode(',', $datesStr);

                // Return an array with driver_id and dates
                return [
                    'driver_id' => $driverId,
                    'dates' => $dates
                ];
            })
            ->all();
    }

    /**
     * Sort drivers by the number of assignments they have in the current month
     * This prioritizes drivers with fewer assignments for fairness
     *
     * @param Collection $drivers
     * @param int $month
     * @param int $year
     * @return Collection
     */
    protected function sortDriversByAssignmentCount(Collection $drivers, int $month, int $year): Collection
    {
        // Get schedule counts for each driver
        $driverCounts = [];

        foreach ($drivers as $driver) {
            // Count schedules from both regular and backup assignments
            $count = Schedule::where(function ($query) use ($driver) {
                    $query->where('driver_id', $driver->id)
                          ->orWhere('backup_driver_id', $driver->id);
                })
                ->whereYear('schedule_date', $year)
                ->whereMonth('schedule_date', $month)
                ->count();

            $driverCounts[$driver->id] = $count;
        }

        // Sort drivers by schedule count (ascending)
        return $drivers->sortBy(function ($driver) use ($driverCounts) {
            return $driverCounts[$driver->id] ?? 0;
        });
    }

    /**
     * Generate schedules for a specific date
     *
     * @param Carbon $currentDate
     * @param array $results
     * @return void
     */
    protected function generateSchedulesForDate(Carbon $currentDate, array &$results): void
    {
        $dateStr = $currentDate->format('Y-m-d');
        $this->messages[] = "Generating schedules for date: {$dateStr}";

        // Get drivers on leave for this date
        $driversOnLeave = $this->getDriversOnLeaveForDate($dateStr);
        $driversOnLeaveIdsSet = array_flip($driversOnLeave->pluck('id')->toArray());

        // Get unavailable units for this date
        $unavailableUnitIds = $this->getUnavailableUnitsForDay($dateStr);

        // Determine resource percentage based on day type (still used for weekend driver pools)
        $resourcePercentage = $this->getResourcePercentageFromUnitRenops($dateStr);

        // Calculate counts for logging
        $totalUnitsCount = $this->units->count();
        $availableUnitsCount = $totalUnitsCount - count($unavailableUnitIds);
        $this->messages[] = "Resource percentage: {$resourcePercentage}%, Available units: {$availableUnitsCount}/{$totalUnitsCount}";

        // Get previous day schedules for shift sequence rules
        $previousDate = $currentDate->copy()->subDay()->format('Y-m-d');
        $previousDaySchedules = $this->getSchedulesForDate($previousDate);

        // Get schedules from two days ago for shift sequence rules
        $twoDaysAgoDate = $currentDate->copy()->subDays(2)->format('Y-m-d');
        $twoDaysAgoSchedules = $this->getSchedulesForDate($twoDaysAgoDate);

        // Track which drivers are already scheduled for this day
        $scheduledDriverIdsForToday = [];

        // Track driver schedule counts for the current period
        $driverScheduleCounts = $this->getDriverScheduleCounts($currentDate);

        // Process morning shift
        $this->messages[] = "Processing morning shift (pagi)";
        $this->processShiftForDate($dateStr, 'pagi', $unavailableUnitIds, $scheduledDriverIdsForToday, $driversOnLeaveIdsSet, $previousDaySchedules, $twoDaysAgoSchedules, $driverScheduleCounts, $results);

        // Process afternoon shift
        $this->messages[] = "Processing afternoon shift (siang)";
        $this->processShiftForDate($dateStr, 'siang', $unavailableUnitIds, $scheduledDriverIdsForToday, $driversOnLeaveIdsSet, $previousDaySchedules, $twoDaysAgoSchedules, $driverScheduleCounts, $results);
    }

    /**
     * Process a specific shift for a date
     *
     * @param string $dateStr
     * @param string $shift
     * @param array $unavailableUnitIds
     * @param array $scheduledDriverIdsForToday
     * @param array $driversOnLeaveIdsSet
     * @param array $previousDaySchedules
     * @param array $twoDaysAgoSchedules
     * @param array $driverScheduleCounts
     * @param array $results
     * @return void
     */
    protected function processShiftForDate(
        string $dateStr,
        string $shift,
        array $unavailableUnitIds,
        array &$scheduledDriverIdsForToday,
        array $driversOnLeaveIdsSet,
        array $previousDaySchedules,
        array $twoDaysAgoSchedules,
        array $driverScheduleCounts,
        array &$results
    ): void {
        // Convert scheduledDriverIdsForToday to a set for O(1) lookups
        $scheduledDriverIdsSet = array_flip($scheduledDriverIdsForToday);

        // Process each available unit
        foreach ($this->units as $unit) {
            // Skip unavailable units
            if (in_array($unit->id, $unavailableUnitIds)) {
                continue;
            }

            // Skip units with no routes
            if ($unit->routes->isEmpty()) {
                $this->messages[] = "Unit {$unit->name} has no routes assigned, skipping.";
                continue;
            }

            // Find a suitable driver for this unit, date, and shift
            $driver = $this->findSuitableDriverForUnitOptimized(
                $unit,
                $dateStr,
                $shift,
                $scheduledDriverIdsSet,
                $driversOnLeaveIdsSet,
                $previousDaySchedules,
                $twoDaysAgoSchedules,
                $driverScheduleCounts
            );

            if ($driver) {
                // Create schedule
                $schedule = new Schedule();
                $schedule->driver_id = $driver->id;
                $schedule->unit_id = $unit->id;
                $schedule->route_id = $unit->routes->first()->id;
                $schedule->schedule_date = $dateStr;
                $schedule->shift = $shift;
                $schedule->save();

                // Update tracking variables
                $scheduledDriverIdsForToday[] = $driver->id;
                $scheduledDriverIdsSet[$driver->id] = true;
                $driverScheduleCounts[$driver->id] = ($driverScheduleCounts[$driver->id] ?? 0) + 1;

                // Update results
                $results['success']++;
                $this->messages[] = "Created schedule for driver {$driver->name} on unit {$unit->name} for {$dateStr} {$shift}";
            } else {
                // No suitable driver found
                $results['failed']++;
                $this->messages[] = "Failed to find suitable driver for unit {$unit->name} on {$dateStr} {$shift}";
            }
        }
    }
}
