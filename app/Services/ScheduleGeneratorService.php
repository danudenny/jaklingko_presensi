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
    protected $batanganDrivers;
    protected $cadanganDrivers;
    protected $messages = [];
    protected $success = 0;
    protected $failed = 0;
    protected $periodDays = 15;
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
    protected $schedulePlannerService;

    public function __construct(
        ScheduleGeneratorUtilityService $utilityService,
        DriverSelectionService $driverSelectionService,
        SchedulePlannerService $schedulePlannerService
    ) {
        $this->utilityService = $utilityService;
        $this->driverSelectionService = $driverSelectionService;
        $this->schedulePlannerService = $schedulePlannerService;
    }

    public function generateSchedules(string $startDate, string $endDate): array
    {
        $startTime = microtime(true);
        $startDate = Carbon::parse($startDate)->startOfDay();
        $endDate = Carbon::parse($endDate)->endOfDay();

        try {
            // Initialize results array
            $results = [
                'success' => 0,
                'failed' => 0,
                'messages' => []
            ];

            // Load all necessary data
            $this->loadData($results);

            if ($this->units->isEmpty()) {
                $results['messages'][] = "No units available for scheduling. Cannot generate schedules.";
                return $results;
            }

            if ($this->batanganDrivers->isEmpty() && $this->cadanganDrivers->isEmpty()) {
                $results['messages'][] = "No drivers available for scheduling. Cannot generate schedules.";
                return $results;
            }

            // Load settings
            $this->renopsSettings = RenopsSettings::getCurrentSettings();
            $this->utilityService->loadDriverScheduleSettings(
                $this->driverScheduleSettings,
                $this->batanganSettings,
                $this->cadanganSettings,
                $this->messages
            );

            // Preload all necessary data
            $this->utilityService->preloadUnitDayOffs($startDate, $endDate, $this->unitDayOffs);
            $this->utilityService->precacheUnitAssignments(
                $this->units,
                $this->batanganDrivers,
                $this->cadanganDrivers,
                $this->unitAssignmentsCache
            );

            // Generate initial schedule plan
            $schedulePlan = $this->schedulePlannerService->generateSchedulePlan(
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d'),
                $this->units,
                $this->batanganDrivers,
                $this->cadanganDrivers,
                $this->batanganSettings,
                $this->cadanganSettings,
                $this->unitDayOffs
            );

            // Optimize the plan to ensure constraints are met
            try {
                $optimizedPlan = $this->schedulePlannerService->optimizeSchedulePlan(
                    $schedulePlan,
                    $startDate->format('Y-m-d'),
                    $endDate->format('Y-m-d'),
                    $this->units,
                    $this->batanganDrivers,
                    $this->cadanganDrivers,
                    $this->unitDayOffs
                );
                
                if (empty($optimizedPlan)) {
                    $results['messages'][] = "No valid schedules could be created. Check that units and drivers exist.";
                    Log::warning("Schedule generation produced empty plan");
                    return $results;
                }
                
                // Create actual schedules from the plan
                $scheduleResults = $this->schedulePlannerService->createSchedulesFromPlan(
                    $optimizedPlan,
                    $startDate->format('Y-m-d'),
                    $endDate->format('Y-m-d'),
                    $this->units,
                    $this->batanganDrivers,
                    $this->cadanganDrivers,
                    $this->unitDayOffs
                );
                
                // Update results with schedule creation results
                $this->success += $scheduleResults['success'];
                $this->failed += $scheduleResults['failed'];
                $this->messages = array_merge($this->messages, $scheduleResults['messages']);
            } catch (\Exception $e) {
                $results['messages'][] = "Error generating schedules: " . $e->getMessage();
                Log::error("Error in schedule generation: " . $e->getMessage(), [
                    'trace' => $e->getTraceAsString()
                ]);
                return $results;
            }

            $endTime = microtime(true);
            $executionTime = round($endTime - $startTime, 2);

            $results['success'] = $this->success;
            $results['failed'] = $this->failed;
            $results['messages'] = $this->messages;
            $results['messages'][] = "Schedule generation completed in {$executionTime} seconds.";

            return $results;
        } catch (\Exception $e) {
            $this->messages[] = "Error generating schedules: " . $e->getMessage();
            Log::error("Error generating schedules: " . $e->getMessage());
            Log::error($e->getTraceAsString());

            return [
                'success' => $this->success,
                'failed' => $this->failed,
                'messages' => $this->messages
            ];
        }

        $results = [
            'success' => 0,
            'failed' => 0,
            'messages' => []
        ];

        $this->loadData($results);

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

        set_time_limit(300);

        $chunkSize = 5;
        $chunks = ceil($totalDays / $chunkSize);

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

            $currentDate = $chunkStartDate->copy();

            while ($currentDate->lte($chunkEndDate)) {
                $dateStr = $currentDate->format('Y-m-d');
                $daysProcessed++;
                $this->weekendDriversPool = [];

                $unavailableUnitIds = $this->utilityService->getUnavailableUnitsForDay($dateStr, $this->unitDayOffs);
                $resourcePercentage = $this->utilityService->getResourcePercentageFromUnitRenops($dateStr, [
                    'weekend_percentage' => $this->renopsSettings ? $this->renopsSettings->weekend_threshold : 80,
                    'holiday_percentage' => $this->renopsSettings ? $this->renopsSettings->holiday_threshold : 80,
                ]);

                $totalUnitsCount = $this->units->count();
                $unavailableUnitsCount = count($unavailableUnitIds);
                $availableUnitsCount = $totalUnitsCount - $unavailableUnitsCount;

                $existingSchedulesForDate = isset($this->existingSchedulesCache[$dateStr]) 
                    ? collect($this->existingSchedulesCache[$dateStr]) 
                    : collect();
                    
                $scheduledUnitIdsMorning = $existingSchedulesForDate->where('shift', 'pagi')->pluck('unit_id')->toArray();
                $scheduledUnitIdsEvening = $existingSchedulesForDate->where('shift', 'siang')->pluck('unit_id')->toArray();
                $scheduledDriverIds = $existingSchedulesForDate->pluck('driver_id')->toArray();
                $driversOnLeaveIds = $driversOnLeaveByDate[$dateStr] ?? [];
                $dayDriversScheduled = []; 
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

                $scheduledDriverIdsForAfternoon = array_merge($scheduledDriverIds, array_keys($dayDriversScheduled));
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
                
                $availableUnitsCount = count($this->units) - count($unavailableUnitIds);
                $unitsWithMorningShift = $morningResults['scheduled_units'] ?? [];
                $unitsWithAfternoonShift = $afternoonResults['scheduled_units'] ?? [];
                $unitsMissingMorning = array_diff(
                    array_column($this->units->whereNotIn('id', $unavailableUnitIds)->all(), 'id'), 
                    $unitsWithMorningShift
                );
                $unitsMissingAfternoon = array_diff(
                    array_column($this->units->whereNotIn('id', $unavailableUnitIds)->all(), 'id'), 
                    $unitsWithAfternoonShift
                );
                
                if (count($unitsMissingMorning) > 0 || count($unitsMissingAfternoon) > 0) {
                    $updatedScheduledDriverIds = array_merge($scheduledDriverIds, array_keys($dayDriversScheduled));
                    $rescheduleResults = [
                        'morning_added' => 0,
                        'afternoon_added' => 0
                    ];
                    $rescheduleResults = $this->attemptRescheduleForMissingShifts(
                        $dateStr,
                        $unitsMissingMorning,
                        $unitsMissingAfternoon,
                        $updatedScheduledDriverIds,
                        $driversOnLeaveIds,
                        $dayDriversScheduled
                    );
                }
                
                if ((count($unitsMissingMorning) - ($rescheduleResults['morning_added'] ?? 0) > 0) || 
                    (count($unitsMissingAfternoon) - ($rescheduleResults['afternoon_added'] ?? 0) > 0)) {
                }

                if ($daysProcessed % 3 == 0 || $currentDate->equalTo($endDate)) {
                    $progressPercent = round(($daysProcessed / $totalDays) * 100);
                }

                $currentDate->addDay();
            }
        }

        $this->logDriverScheduleStats($startDate->format('Y-m-d'), $endDate->format('Y-m-d'), 'Pre-balancing');
        $this->balanceSchedulesInPeriod($startDate, $endDate, $results);
        $this->logDriverScheduleStats($startDate->format('Y-m-d'), $endDate->format('Y-m-d'), 'Final');

        $endTime = microtime(true);
        $executionTime = round($endTime - $startTime, 2);

        $results['success'] = $this->success;
        $results['failed'] = $this->failed;
        $results['messages'] = $this->messages;

        return $results;
    }

    protected function loadData(array &$results): void
    {
        try {
            $this->routes = collect();
            $this->units = collect();
            $this->batanganDrivers = collect();
            $this->cadanganDrivers = collect();
            $this->routes = Route::all();
            $this->units = Unit::with('routes')->get();
            $allDrivers = Driver::with(['units', 'routes'])->get();
            $this->batanganDrivers = $allDrivers->where('type', 'batangan');
            $this->cadanganDrivers = $allDrivers->where('type', 'cadangan');
            $this->driverUnitMap = [];
            
            foreach ($allDrivers as $driver) {
                if ($driver->units && $driver->units->isNotEmpty()) {
                    $this->driverUnitMap[$driver->id] = $driver->units->pluck('id')->toArray();
                }
            }

        } catch (\Exception $e) {
            $results['messages'][] = "ERROR loading data: " . $e->getMessage();
        }
    }

    protected function generateBatanganSchedulePlan($startDate, $endDate)
    {
        $start = Carbon::parse($startDate)->copy();
        $end = Carbon::parse($endDate)->copy();
        $days = [];
        $weekdays = [];
        $weekends = [];
        
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $dateStr = $date->format('Y-m-d');
            $days[] = $dateStr;
            
            if ($date->isWeekend()) {
                $weekends[] = $dateStr;
            } else {
                $weekdays[] = $dateStr;
            }
        }
        
        $totalDays = count($days);
        $targetDays = isset($this->batanganSettings['min_schedules']) ? $this->batanganSettings['min_schedules'] : min(13, $totalDays);
        $targetDays = min($targetDays, $totalDays);
        
        $unitBatanganDriverCounts = [];
        foreach ($this->units as $unit) {
            $unitBatanganDrivers = $this->batanganDrivers->filter(function($driver) use ($unit) {
                return $driver->units->contains('id', $unit->id);
            });
            
            $unitBatanganDriverCounts[$unit->id] = $unitBatanganDrivers->count();
        }
        
        foreach ($this->batanganDrivers as $driver) {
            foreach ($driver->units as $unit) {
                $driverCount = $unitBatanganDriverCounts[$unit->id] ?? 1;
                $adjustedTargetDays = min($targetDays, ceil($totalDays * 0.95 / $driverCount));
                $targetWeekends = min(
                    (int)ceil(count($weekends) / $driverCount), 
                    (int)ceil($adjustedTargetDays * count($weekends) / $totalDays)
                );
                $targetWeekdays = $adjustedTargetDays - $targetWeekends;
                $selectedDays = [];
                
                $weekendCopy = $weekends;
                shuffle($weekendCopy);
                $selectedWeekends = array_slice($weekendCopy, 0, $targetWeekends);
                $selectedDays = array_merge($selectedDays, $selectedWeekends);
                
                $weekdayCopy = $weekdays;
                shuffle($weekdayCopy);
                $selectedWeekdays = array_slice($weekdayCopy, 0, $targetWeekdays);
                $selectedDays = array_merge($selectedDays, $selectedWeekdays);
                
                sort($selectedDays);
                $plan = [];
                $lastShift = null;
                $lastDay = null;
                $morningCount = 0;
                $afternoonCount = 0;
                
                foreach ($selectedDays as $i => $day) {
                    if ($lastDay) {
                        $prevIdx = array_search($lastDay, $days);
                        $currIdx = array_search($day, $days);
                        $gap = $currIdx - $prevIdx;
                        
                        if ($gap > 1) {
                            $lastShift = null;
                        }
                    }
                    
                    if ($lastShift === 'siang') {
                        $shift = 'siang';
                    } else {
                        if ($morningCount < $afternoonCount) {
                            $shift = 'pagi';
                        } elseif ($afternoonCount < $morningCount) {
                            $shift = 'siang';
                        } else {
                            $shift = $i % 2 == 0 ? 'pagi' : 'siang';
                        }
                    }
                    
                    $plan[$day] = $shift;
                    $lastShift = $shift;
                    $lastDay = $day;
                    
                    if ($shift === 'pagi') {
                        $morningCount++;
                    } else {
                        $afternoonCount++;
                    }
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

        $scheduledDriverIdsSet = array_flip($scheduledDriverIds);
        $scheduledUnitIdsSet = array_flip($scheduledUnitIds);
        $driversOnLeaveIdsSet = array_flip($driversOnLeaveIds);

        static $scheduledDriverShiftsSet = [];
        if (!isset($scheduledDriverShiftsSet[$dateStr])) {
            $scheduledDriverShiftsSet[$dateStr] = [];
        }

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

        $driverScheduleCounts = $this->getDriverScheduleCountsForPeriod($periodStartDate, $periodEndDate);
        $sortedUnits = $this->units->sortBy('unit_number');
        $schedulesToCreate = [];
        $unitsWithBatanganDrivers = [];
        
        foreach ($sortedUnits as $unit) {
            $unitBatanganDrivers = $this->batanganDrivers->filter(function($driver) use ($unit) {
                return $driver->units->contains('id', $unit->id);
            });
            
            if (!$unitBatanganDrivers->isEmpty()) {
                $unitsWithBatanganDrivers[$unit->id] = true;
            }
        }
        
        foreach ($sortedUnits as $unit) {
            if (in_array($unit->id, $unavailableUnitIds)) {
                continue;
            }

            if (isset($scheduledUnitIdsSet[$unit->id])) {
                continue;
            }

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

            if ($driver) {
                if (isset($dayDriversScheduled[$driver->id])) {
                    continue;
                }
                
                $plan = $this->batanganSchedulePlan[$unit->id][$driver->id] ?? [];
                if (!isset($plan[$dateStr]) || $plan[$dateStr] !== $shift) {
                    continue;
                }
                
                $routeId = $unit->routes->isNotEmpty() ? $unit->routes->first()->id : null;
                if (!$routeId) {
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

        foreach ($sortedUnits as $unit) {
            if (in_array($unit->id, $unavailableUnitIds)) {
                continue;
            }
            if (isset($scheduledUnitIdsSet[$unit->id])) {
                continue;
            }
            
            $priority = isset($unitsWithBatanganDrivers[$unit->id]) ? 0 : 1;
            
            if ($priority === 0) {
                continue;
            }
            
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
                if (isset($scheduledDriverShiftsSet[$dateStr][$driver->id])) {
                    continue;
                }
                $routeId = $unit->routes->isNotEmpty() ? $unit->routes->first()->id : null;
                if (!$routeId) {
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
        
        foreach ($sortedUnits as $unit) {
            if (in_array($unit->id, $unavailableUnitIds)) {
                continue;
            }
            if (isset($scheduledUnitIdsSet[$unit->id])) {
                continue;
            }
            
            if (!isset($unitsWithBatanganDrivers[$unit->id])) {
                continue;
            }
            
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
                if (isset($scheduledDriverShiftsSet[$dateStr][$driver->id])) {
                    continue;
                }
                $routeId = $unit->routes->isNotEmpty() ? $unit->routes->first()->id : null;
                if (!$routeId) {
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
        
        if (!empty($schedulesToCreate)) {
            list($success, $failed) = $this->utilityService->createSchedules($schedulesToCreate, $this->messages);
            $this->success += $success;
            $this->failed += $failed;
            
            $scheduledUnitIds = array_unique(array_column($schedulesToCreate, 'unit_id'));
            $result['units_scheduled'] = count($scheduledUnitIds);
            $result['scheduled_units'] = $scheduledUnitIds;
            $result['drivers_scheduled'] = count(array_unique(array_column($schedulesToCreate, 'driver_id')));
            
            foreach ($schedulesToCreate as $schedule) {
                $dayDriversScheduled[$schedule['driver_id']] = $schedule['shift'];
            }
        }
        
        return $result;
    }
    
    protected function getDriverScheduleCountsForPeriod(string $startDateStr, string $endDateStr): array
    {
        $driverCounts = [];
        $existingSchedules = Schedule::select('driver_id', DB::raw('COUNT(*) as count'))
            ->whereBetween('schedule_date', [$startDateStr, $endDateStr])
            ->groupBy('driver_id')
            ->get();
            
        foreach ($existingSchedules as $schedule) {
            $driverCounts[$schedule->driver_id] = $schedule->count;
        }
        
        return $driverCounts;
    }

    protected function balanceSchedulesInPeriod(Carbon $startDate, Carbon $endDate, array &$results): void
    {
        $allSchedules = Schedule::whereBetween('schedule_date', [
                $startDate->format('Y-m-d'), 
                $endDate->format('Y-m-d')
            ])
            ->get();
            
        if ($allSchedules->isEmpty()) {
            $this->createDriverScheduleHistoryRecords($startDate, $endDate);
            return;
        }
            
        $schedulesByDriver = $allSchedules->groupBy('driver_id');
        $batanganSchedules = [];
        $cadanganSchedules = [];
        
        foreach ($schedulesByDriver as $driverId => $driverSchedules) {
            $driver = $this->batanganDrivers->firstWhere('id', $driverId);
            $count = $driverSchedules->count();
            
            if ($driver) {
                $batanganSchedules[$driverId] = $count;
            } else {
                $driver = $this->cadanganDrivers->firstWhere('id', $driverId);
                if ($driver) {
                    $cadanganSchedules[$driverId] = $count;
                }
            }
        }
        
        $batanganAverage = !empty($batanganSchedules) ? array_sum($batanganSchedules) / count($batanganSchedules) : 0;
        $cadanganAverage = !empty($cadanganSchedules) ? array_sum($cadanganSchedules) / count($cadanganSchedules) : 0;
        
        if (!empty($cadanganSchedules) && !empty($batanganSchedules)) {
            $targetRatio = 0.85; // cadangan drivers should get about 85% of what batangan drivers get
            $targetCadanganAverage = $batanganAverage * $targetRatio;
            
            if ($cadanganAverage > $targetCadanganAverage) {
                $this->redistributeExcessCadanganSchedules(
                    $allSchedules, 
                    $cadanganSchedules, 
                    $batanganSchedules, 
                    $cadanganAverage, 
                    $targetCadanganAverage
                );
                
                $updatedSchedules = Schedule::whereBetween('schedule_date', [
                    $startDate->format('Y-m-d'), 
                    $endDate->format('Y-m-d')
                ])->get();
                
                $updatedSchedulesByDriver = $updatedSchedules->groupBy('driver_id');
                $updatedBatanganSchedules = [];
                $updatedCadanganSchedules = [];
                
                foreach ($updatedSchedulesByDriver as $driverId => $driverSchedules) {
                    $driver = $this->batanganDrivers->firstWhere('id', $driverId);
                    $count = $driverSchedules->count();
                    
                    if ($driver) {
                        $updatedBatanganSchedules[$driverId] = $count;
                    } else {
                        $driver = $this->cadanganDrivers->firstWhere('id', $driverId);
                        if ($driver) {
                            $updatedCadanganSchedules[$driverId] = $count;
                        }
                    }
                }
                
                $updatedBatanganAvg = !empty($updatedBatanganSchedules) ? 
                    array_sum($updatedBatanganSchedules) / count($updatedBatanganSchedules) : 0;
                
                $updatedCadanganAvg = !empty($updatedCadanganSchedules) ? 
                    array_sum($updatedCadanganSchedules) / count($updatedCadanganSchedules) : 0;
                
                $updatedTargetAvg = $updatedBatanganAvg * $targetRatio;
                
                if ($updatedCadanganAvg > $updatedTargetAvg) {
                    $this->redistributeExcessCadanganSchedules(
                        $updatedSchedules, 
                        $updatedCadanganSchedules, 
                        $updatedBatanganSchedules, 
                        $updatedCadanganAvg, 
                        $updatedTargetAvg
                    );
                } else {
                    $this->messages[] = "After redistribution: Cadangan drivers now have appropriate schedule levels. " .
                                        "Average: " . round($updatedCadanganAvg, 1) . ", Target: " . round($updatedTargetAvg, 1);
                }
            } else {
                $this->messages[] = "Cadangan drivers already have fewer schedules than batangan drivers. No rebalancing needed.";
            }
        }
        
        $this->createDriverScheduleHistoryRecords($startDate, $endDate);
    }
    
    protected function createDriverScheduleHistoryRecords(Carbon $startDate, Carbon $endDate): void
    {
        try {
            $schedules = Schedule::whereBetween('schedule_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->get();
                
            $schedulesByDriver = $schedules->groupBy('driver_id');
            
            foreach ($schedulesByDriver as $driverId => $driverSchedules) {
                $totalSchedules = $driverSchedules->count();
                $morningShifts = $driverSchedules->where('shift', 'pagi')->count();
                $afternoonShifts = $driverSchedules->where('shift', 'siang')->count();
                
                $driver = Driver::find($driverId);
                $targetCount = 13; // Default target
                if ($driver) {
                    if ($driver->type === 'batangan' && isset($this->batanganSettings['min_schedules'])) {
                        $targetCount = $this->batanganSettings['min_schedules'];
                    } else if ($driver->type === 'cadangan' && isset($this->cadanganSettings['min_schedules'])) {
                        $targetCount = $this->cadanganSettings['min_schedules'];
                    }
                }
                
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
            
        } catch (\Exception $e) {
            Log::error($e->getTraceAsString());
        }
    }
    
    protected function attemptRescheduleForMissingShifts(
        string $dateStr,
        array $unitsMissingMorning,
        array $unitsMissingAfternoon,
        array $scheduledDriverIds,
        array $driversOnLeaveIds,
        array &$dayDriversScheduled
    ): array {
        return $this->enhancedAttemptRescheduleForMissingShifts(
            $dateStr,
            $scheduledDriverIds,
            $driversOnLeaveIds,
            $unitsMissingMorning,
            $unitsMissingAfternoon,
            $dayDriversScheduled
        );
        $cadanganSettings = $this->cadanganSettings;
        $result = [
            'morning_added' => 0,
            'afternoon_added' => 0
        ];
        
        $allScheduledDriverIdsSet = array_flip($scheduledDriverIds);
        foreach ($dayDriversScheduled as $driverId => $shift) {
            $allScheduledDriverIdsSet[$driverId] = true;
        }
        
        $totalDriversForDay = count($allScheduledDriverIdsSet);
        $driversOnLeaveIdsSet = array_flip($driversOnLeaveIds);
        
        if (!empty($unitsMissingMorning)) {
            $morningSchedulesToCreate = [];
            
            if ($totalDriversForDay > 2) {
                $this->messages[] = "Already exceeded maximum of 2 drivers for {$dateStr}. Skipping morning assignments.";
            } else {
                foreach ($unitsMissingMorning as $unitId) {
                    $unit = $this->units->firstWhere('id', $unitId);
                    if (!$unit) {
                        continue;
                    }
                    
                    $availableDrivers = $this->batanganDrivers->filter(function($driver) use ($allScheduledDriverIdsSet, $driversOnLeaveIdsSet) {
                        return !isset($allScheduledDriverIdsSet[$driver->id]) && !isset($driversOnLeaveIdsSet[$driver->id]);
                    });
                    
                    $availableDrivers = $availableDrivers->filter(function($driver) use ($unitId) {
                        return $driver->units->contains('id', $unitId);
                    });
                    
                    if ($availableDrivers->isEmpty()) {
                        $cadanganDrivers = $this->cadanganDrivers->filter(function($driver) use ($allScheduledDriverIdsSet, $driversOnLeaveIdsSet) {
                            return !isset($allScheduledDriverIdsSet[$driver->id]) && !isset($driversOnLeaveIdsSet[$driver->id]);
                        });
                        
                        $availableDrivers = $cadanganDrivers->filter(function($driver) use ($unitId) {
                            return $driver->units->contains('id', $unitId);
                        });
                        
                        if (!$availableDrivers->isEmpty()) {
                            $driverScheduleCounts = $this->getDriverScheduleCountsForPeriod(
                                Carbon::parse($dateStr)->startOfMonth()->format('Y-m-d'),
                                Carbon::parse($dateStr)->endOfMonth()->format('Y-m-d')
                            );
                            
                            $availableDrivers = $availableDrivers->filter(function($driver) use ($driverScheduleCounts) {
                                $currentCount = $driverScheduleCounts[$driver->id] ?? 0;
                                return $currentCount < 11; // Cadangan max is 11
                            });
                        }
                    } else {
                        $driverScheduleCounts = $this->getDriverScheduleCountsForPeriod(
                            Carbon::parse($dateStr)->startOfMonth()->format('Y-m-d'),
                            Carbon::parse($dateStr)->endOfMonth()->format('Y-m-d')
                        );
                        
                        $availableDrivers = $availableDrivers->sortBy(function($driver) use ($driverScheduleCounts) {
                            $currentCount = $driverScheduleCounts[$driver->id] ?? 0;
                            return $currentCount >= 13 ? $currentCount + 100 : $currentCount;
                        });
                    }
                    
                    if (!$availableDrivers->isEmpty()) {
                        $driver = $availableDrivers->first(function($driver) use ($unit, $unitId) {
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
                            }
                        }
                    }
                }
            }
            
            if (!empty($morningSchedulesToCreate)) {
                list($success, $failed) = $this->utilityService->createSchedules($morningSchedulesToCreate, $this->messages);
                $this->success += $success;
                $this->failed += $failed;
                $result['morning_added'] = $success;
            }
        }
        
        if (!empty($unitsMissingAfternoon)) {
            $afternoonSchedulesToCreate = [];
            $totalDriversForDay = count($allScheduledDriverIdsSet);
            
            if ($totalDriversForDay >= 2) {
                $this->messages[] = "Already reached maximum of 2 drivers for {$dateStr}. Skipping afternoon assignments.";
            } else {
                foreach ($unitsMissingAfternoon as $unitId) {
                    if ($totalDriversForDay >= 2) {
                        break; // Stop if we've reached the 2-driver limit
                    }
                    
                    $unit = $this->units->firstWhere('id', $unitId);
                    if (!$unit) {
                        continue;
                    }
                    
                    $availableDrivers = $this->batanganDrivers->filter(function($driver) use ($allScheduledDriverIdsSet, $driversOnLeaveIdsSet) {
                        return !isset($allScheduledDriverIdsSet[$driver->id]) && !isset($driversOnLeaveIdsSet[$driver->id]);
                    });
                    
                    $availableDrivers = $availableDrivers->filter(function($driver) use ($unitId) {
                        return $driver->units->contains('id', $unitId);
                    });
                    
                    if ($availableDrivers->isEmpty()) {
                        $cadanganDrivers = $this->cadanganDrivers->filter(function($driver) use ($allScheduledDriverIdsSet, $driversOnLeaveIdsSet) {
                            return !isset($allScheduledDriverIdsSet[$driver->id]) && !isset($driversOnLeaveIdsSet[$driver->id]);
                        });
                        
                        $availableDrivers = $cadanganDrivers->filter(function($driver) use ($unitId) {
                            return $driver->units->contains('id', $unitId);
                        });
                        
                        if (!$availableDrivers->isEmpty()) {
                            $driverScheduleCounts = $this->getDriverScheduleCountsForPeriod(
                                Carbon::parse($dateStr)->startOfMonth()->format('Y-m-d'),
                                Carbon::parse($dateStr)->endOfMonth()->format('Y-m-d')
                            );
                            
                            $availableDrivers = $availableDrivers->filter(function($driver) use ($driverScheduleCounts) {
                                $currentCount = $driverScheduleCounts[$driver->id] ?? 0;
                                return $currentCount < 11; // Cadangan max is 11
                            });
                        }
                    } else {
                        $driverScheduleCounts = $this->getDriverScheduleCountsForPeriod(
                            Carbon::parse($dateStr)->startOfMonth()->format('Y-m-d'),
                            Carbon::parse($dateStr)->endOfMonth()->format('Y-m-d')
                        );
                        
                        $availableDrivers = $availableDrivers->sortBy(function($driver) use ($driverScheduleCounts) {
                            $currentCount = $driverScheduleCounts[$driver->id] ?? 0;
                            return $currentCount >= 13 ? $currentCount + 100 : $currentCount;
                        });
                    }
                    
                    if (!$availableDrivers->isEmpty()) {
                        $driver = $availableDrivers->first(function($driver) use ($unit, $unitId) {
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
                            }
                        }
                    }
                }
            }
            
            if (!empty($afternoonSchedulesToCreate)) {
                list($success, $failed) = $this->utilityService->createSchedules($afternoonSchedulesToCreate, $this->messages);
                $this->success += $success;
                $this->failed += $failed;
                $result['afternoon_added'] = $success;
            }
        }
        
        return $result;
    }
    
    protected function rebalanceCadanganSchedules(Collection $schedules): void 
    {
        $schedulesByDriver = $schedules->groupBy('driver_id');
        $batanganSchedules = [];
        $cadanganSchedules = [];
        $targetRatio = $this->cadanganSettings['ratio'] ?? 0.85;
        
        // Group schedules by driver type
        foreach ($schedulesByDriver as $driverId => $driverSchedules) {
            $driver = $this->batanganDrivers->firstWhere('id', $driverId);
            $count = $driverSchedules->count();
            
            if ($driver) {
                $batanganSchedules[$driverId] = $count;
            } else {
                $driver = $this->cadanganDrivers->firstWhere('id', $driverId);
                if ($driver) {
                    $cadanganSchedules[$driverId] = $count;
                }
            }
        }
        
        // Calculate averages
        $batanganAvg = !empty($batanganSchedules) ? array_sum($batanganSchedules) / count($batanganSchedules) : 0;
        $cadanganAvg = !empty($cadanganSchedules) ? array_sum($cadanganSchedules) / count($cadanganSchedules) : 0;
        $targetAvg = $batanganAvg * $targetRatio;
        
        // Check if rebalancing is needed
        if ($cadanganAvg > $targetAvg) {
            $this->redistributeExcessCadanganSchedules(
                $schedules, 
                $cadanganSchedules, 
                $batanganSchedules,
                $cadanganAvg,
                $targetAvg
            );
            
            // Validate results
            $this->validateScheduleDistribution($schedules);
        }
    }
    
    protected function validateScheduleDistribution(Collection $schedules): void
    {
        $schedulesByDriver = $schedules->groupBy('driver_id');
        $batanganCount = $this->batanganDrivers->count();
        $cadanganCount = $this->cadanganDrivers->count();
        
        if ($batanganCount > 0 && $cadanganCount > 0) {
            $targetRatio = $this->cadanganSettings['ratio'] ?? 0.85;
            
            $batanganTotal = 0;
            $cadanganTotal = 0;
            
            foreach ($schedulesByDriver as $driverId => $driverSchedules) {
                if ($this->batanganDrivers->contains('id', $driverId)) {
                    $batanganTotal += $driverSchedules->count();
                } elseif ($this->cadanganDrivers->contains('id', $driverId)) {
                    $cadanganTotal += $driverSchedules->count();
                }
            }
            
            $batanganAvg = $batanganTotal / $batanganCount;
            $cadanganAvg = $cadanganTotal / $cadanganCount;
            $actualRatio = $cadanganAvg / $batanganAvg;
            
            if (abs($actualRatio - $targetRatio) > 0.1) { // 10% tolerance
                $this->messages[] = "Warning: Schedule distribution ratio ({$actualRatio}) differs significantly from target ({$targetRatio})";
            }
        }
    }
}