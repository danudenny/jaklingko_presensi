<?php

namespace App\Services;

use App\Models\Driver;
use App\Models\Unit;
use App\Models\Schedule;
use App\Models\DriverHistory;
use App\Models\DriverScheduleHistory;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SchedulePlannerService
{
    private $driverSelectionService;
    private $utilityService;

    public function __construct(
        DriverSelectionService $driverSelectionService,
        ScheduleGeneratorUtilityService $utilityService
    ) {
        $this->driverSelectionService = $driverSelectionService;
        $this->utilityService = $utilityService;
    }

    public function generateSchedulePlan(
        string $startDate,
        string $endDate,
        Collection $units,
        Collection $batanganDrivers,
        Collection $cadanganDrivers,
        array $batanganSettings,
        array $cadanganSettings, 
        array $unavailableDays
    ): array {
        Log::info("Starting generateSchedulePlan", [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'unitsCount' => $units->count(),
            'batanganDriversCount' => $batanganDrivers->count(),
            'cadanganDriversCount' => $cadanganDrivers->count(),
            'batanganSettings' => $batanganSettings,
            'cadanganSettings' => $cadanganSettings,
            'unavailableDays' => count($unavailableDays)
        ]);

        if ($units->isEmpty()) {
            Log::error("No units provided for schedule generation");
            return [];
        }

        if ($batanganDrivers->isEmpty() && $cadanganDrivers->isEmpty()) {
            Log::error("No drivers provided for schedule generation");
            return [];
        }

        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        $schedulePlan = [];
        $driverScheduleCounts = [];
        $lastShiftByDriver = [];
        $unitAssignments = [];
        $routeAssignments = [];
        $dailyDriverAssignments = []; // Track which drivers are assigned on each day
        $driverShiftHistory = []; // Track shift history for each driver with dates
        
        foreach ($units as $unit) {
            if (!isset($unitAssignments[$unit->id])) {
                $unitAssignments[$unit->id] = [];
            }
            
            $batanganCount = 0;
            $batanganDrivers->each(function($driver) use ($unit, &$unitAssignments, &$batanganCount) {
                if ($driver->units->contains('id', $unit->id)) {
                    $unitAssignments[$unit->id][$driver->id] = 'batangan';
                    $batanganCount++;
                }
            });
            
            $cadanganCount = 0;
            $cadanganDrivers->each(function($driver) use ($unit, &$unitAssignments, &$cadanganCount) {
                if ($driver->units->contains('id', $unit->id)) {
                    $unitAssignments[$unit->id][$driver->id] = 'cadangan';
                    $cadanganCount++;
                }
            });
            
            Log::info("Unit {$unit->id} assigned drivers: $batanganCount batangan, $cadanganCount cadangan");
            
            if ($unit->routes->isNotEmpty()) {
                $routeId = $unit->routes->first()->id;
                if (!isset($routeAssignments[$routeId])) {
                    $routeAssignments[$routeId] = [];
                }
                foreach ($unitAssignments[$unit->id] as $driverId => $type) {
                    $routeAssignments[$routeId][$driverId] = $type;
                }
            }
        }
        
        foreach ($batanganDrivers as $driver) {
            $driverScheduleCounts[$driver->id] = [
                'type' => 'batangan',
                'total' => 0,
                'morning' => 0,
                'afternoon' => 0
            ];
            $lastShiftByDriver[$driver->id] = null;
            $driverShiftHistory[$driver->id] = []; // Initialize shift history
        }
        
        foreach ($cadanganDrivers as $driver) {
            $driverScheduleCounts[$driver->id] = [
                'type' => 'cadangan',
                'total' => 0,
                'morning' => 0,
                'afternoon' => 0
            ];
            $lastShiftByDriver[$driver->id] = null;
            $driverShiftHistory[$driver->id] = []; // Initialize shift history
        }

        $current = $start->copy();
        while ($current <= $end) {
            $dateStr = $current->format('Y-m-d');
            Log::info("Processing date: $dateStr");
            
            $schedulePlan[$dateStr] = [];
            $dailyDriverAssignments[$dateStr] = []; // Initialize empty array for tracking drivers on this day
            
            foreach ($units as $unit) {
                if (isset($unavailableDays[$dateStr]) && in_array($unit->id, $unavailableDays[$dateStr])) {
                    Log::info("Unit {$unit->id} is unavailable on $dateStr");
                    continue;
                }

                if (!isset($unitAssignments[$unit->id]) || empty($unitAssignments[$unit->id])) {
                    Log::warning("No drivers assigned to unit {$unit->id}");
                    continue;
                }

                Log::info("Processing unit {$unit->id} for date $dateStr", [
                    'availableDrivers' => $unitAssignments[$unit->id]
                ]);

                $schedulePlan[$dateStr][$unit->id] = [
                    'morning' => [],
                    'afternoon' => []
                ];
                
                $assignedMorning = false;
                if (isset($unitAssignments[$unit->id])) {
                    $batanganOption = $this->findBestDriver(
                        $unitAssignments[$unit->id],
                        'batangan',
                        'pagi',
                        $driverScheduleCounts,
                        $lastShiftByDriver,
                        $batanganSettings,
                        $dailyDriverAssignments,
                        $dateStr,
                        $driverShiftHistory
                    );
                    
                    Log::info("Batangan morning option for unit {$unit->id}", [
                        'date' => $dateStr,
                        'result' => $batanganOption,
                        'availableDrivers' => array_filter($unitAssignments[$unit->id], fn($type) => $type === 'batangan')
                    ]);
                    
                    if ($batanganOption) {
                        $schedulePlan[$dateStr][$unit->id]['morning'] = [
                            'type' => 'batangan',
                            'driver_id' => $batanganOption
                        ];
                        $driverScheduleCounts[$batanganOption]['total']++;
                        $driverScheduleCounts[$batanganOption]['morning']++;
                        $lastShiftByDriver[$batanganOption] = 'pagi';
                        $assignedMorning = true;
                        $dailyDriverAssignments[$dateStr][$batanganOption] = true; // Mark this driver as assigned for this day
                        
                        // Record shift history
                        $driverShiftHistory[$batanganOption][] = [
                            'date' => $dateStr,
                            'shift' => 'pagi',
                            'unit_id' => $unit->id
                        ];
                    }
                    
                    if (!$assignedMorning) {
                        $cadanganOption = $this->findBestDriver(
                            $unitAssignments[$unit->id],
                            'cadangan',
                            'pagi',
                            $driverScheduleCounts,
                            $lastShiftByDriver,
                            $cadanganSettings,
                            $dailyDriverAssignments,
                            $dateStr,
                            $driverShiftHistory
                        );
                        
                        if ($cadanganOption) {
                            $schedulePlan[$dateStr][$unit->id]['morning'] = [
                                'type' => 'cadangan',
                                'driver_id' => $cadanganOption
                            ];
                            $driverScheduleCounts[$cadanganOption]['total']++;
                            $driverScheduleCounts[$cadanganOption]['morning']++;
                            $lastShiftByDriver[$cadanganOption] = 'pagi';
                            $assignedMorning = true;
                            $dailyDriverAssignments[$dateStr][$cadanganOption] = true; // Mark this driver as assigned for this day
                            
                            // Record shift history
                            $driverShiftHistory[$cadanganOption][] = [
                                'date' => $dateStr,
                                'shift' => 'pagi',
                                'unit_id' => $unit->id
                            ];
                        }
                    }
                }
                
                $assignedAfternoon = false;
                if (isset($unitAssignments[$unit->id])) {
                    $batanganOption = $this->findBestDriver(
                        $unitAssignments[$unit->id],
                        'batangan',
                        'siang',
                        $driverScheduleCounts,
                        $lastShiftByDriver,
                        $batanganSettings,
                        $dailyDriverAssignments,
                        $dateStr,
                        $driverShiftHistory
                    );
                    
                    if ($batanganOption) {
                        $schedulePlan[$dateStr][$unit->id]['afternoon'] = [
                            'type' => 'batangan',
                            'driver_id' => $batanganOption
                        ];
                        $driverScheduleCounts[$batanganOption]['total']++;
                        $driverScheduleCounts[$batanganOption]['afternoon']++;
                        $lastShiftByDriver[$batanganOption] = 'siang';
                        $assignedAfternoon = true;
                        $dailyDriverAssignments[$dateStr][$batanganOption] = true; // Mark this driver as assigned for this day
                        
                        // Record shift history
                        $driverShiftHistory[$batanganOption][] = [
                            'date' => $dateStr,
                            'shift' => 'siang',
                            'unit_id' => $unit->id
                        ];
                    }
                    
                    if (!$assignedAfternoon) {
                        $cadanganOption = $this->findBestDriver(
                            $unitAssignments[$unit->id],
                            'cadangan',
                            'siang',
                            $driverScheduleCounts,
                            $lastShiftByDriver,
                            $cadanganSettings,
                            $dailyDriverAssignments,
                            $dateStr,
                            $driverShiftHistory
                        );
                        
                        if ($cadanganOption) {
                            $schedulePlan[$dateStr][$unit->id]['afternoon'] = [
                                'type' => 'cadangan',
                                'driver_id' => $cadanganOption
                            ];
                            $driverScheduleCounts[$cadanganOption]['total']++;
                            $driverScheduleCounts[$cadanganOption]['afternoon']++;
                            $lastShiftByDriver[$cadanganOption] = 'siang';
                            $assignedAfternoon = true;
                            $dailyDriverAssignments[$dateStr][$cadanganOption] = true; // Mark this driver as assigned for this day
                            
                            // Record shift history
                            $driverShiftHistory[$cadanganOption][] = [
                                'date' => $dateStr,
                                'shift' => 'siang',
                                'unit_id' => $unit->id
                            ];
                        }
                    }
                }
            }
            
            $current->addDay();
        }

        $scheduledShifts = 0;
        foreach ($schedulePlan as $date => $unitPlans) {
            foreach ($unitPlans as $unitId => $shifts) {
                foreach (['pagi', 'siang'] as $shift) {
                    if (!empty($shifts[$shift]) && isset($shifts[$shift]['driver_id'])) {
                        $scheduledShifts++;
                    }
                }
            }
        }
        
        Log::info("Schedule generation completed", [
            'totalShiftsScheduled' => $scheduledShifts,
            'driverCounts' => $driverScheduleCounts
        ]);

        return $schedulePlan;
    }

    public function optimizeSchedulePlan(array $schedulePlan): array
    {
        Log::info("Starting schedule optimization");
        $driverCounts = [];
        $unitShiftCounts = [];
        
        // Verify all unit IDs exist in the database
        $allUnitIds = [];
        foreach ($schedulePlan as $date => $unitPlans) {
            foreach ($unitPlans as $unitId => $shifts) {
                $allUnitIds[$unitId] = true;
            }
        }
        
        $existingUnits = Unit::whereIn('id', array_keys($allUnitIds))->pluck('id')->toArray();
        $validUnitIds = array_flip($existingUnits);
        
        // Filter out invalid units
        foreach ($schedulePlan as $date => $unitPlans) {
            foreach ($unitPlans as $unitId => $shifts) {
                if (!isset($validUnitIds[$unitId])) {
                    Log::warning("Unit ID $unitId not found in database, removing from schedule plan");
                    unset($schedulePlan[$date][$unitId]);
                }
            }
            
            // If no units remain for this date after filtering, remove the date
            if (empty($schedulePlan[$date])) {
                unset($schedulePlan[$date]);
            }
        }

        // First pass: Count driver assignments and unit shifts
        foreach ($schedulePlan as $date => $unitPlans) {
            foreach ($unitPlans as $unitId => $shifts) {
                // Map between database terminology and schedule plan keys
                $shiftMap = [
                    'pagi' => 'morning',
                    'siang' => 'afternoon'
                ];
                
                foreach (['pagi', 'siang'] as $shift) {
                    $planShiftKey = $shiftMap[$shift];
                    
                    if (isset($shifts[$planShiftKey]['driver_id'])) {
                        $driverId = $shifts[$planShiftKey]['driver_id'];
                        $driverType = $shifts[$planShiftKey]['type'];
                        
                        if (!isset($driverCounts[$driverId])) {
                            $driverCounts[$driverId] = [
                                'type' => $driverType,
                                'count' => 0,
                                'units' => []
                            ];
                        }
                        
                        $driverCounts[$driverId]['count']++;
                        if (!in_array($unitId, $driverCounts[$driverId]['units'])) {
                            $driverCounts[$driverId]['units'][] = $unitId;
                        }
                    }
                }

                // Track shifts per unit per day
                if (!isset($unitShiftCounts[$date][$unitId])) {
                    $unitShiftCounts[$date][$unitId] = 0;
                }
                $unitShiftCounts[$date][$unitId] += isset($shifts['morning']['driver_id']) ? 1 : 0;
                $unitShiftCounts[$date][$unitId] += isset($shifts['afternoon']['driver_id']) ? 1 : 0;
            }
        }

        // Second pass: Apply optimizations
        foreach ($schedulePlan as $date => $unitPlans) {
            foreach ($unitPlans as $unitId => $shifts) {
                // Ensure each unit has both shifts if possible
                if (!isset($unitShiftCounts[$date][$unitId]) || $unitShiftCounts[$date][$unitId] !== 2) {
                    // Map between shifts in plan vs database terminology
                    $shiftMap = [
                        'pagi' => 'morning',
                        'siang' => 'afternoon'
                    ];
                    
                    foreach (['pagi', 'siang'] as $shift) {
                        $planShiftKey = $shiftMap[$shift];
                        
                        if (!isset($shifts[$planShiftKey]['driver_id'])) {
                            // Find available driver who:
                            // 1. Is not already assigned that day
                            // 2. Has not reached their schedule limit
                            // 3. Is qualified for the unit
                            // 4. Would maintain shift balance
                            $availableDriver = $this->findAvailableDriverForShift(
                                $unitId,
                                $date,
                                $shift,
                                $schedulePlan,
                                $driverCounts
                            );
                            
                            if ($availableDriver) {
                                $schedulePlan[$date][$unitId][$planShiftKey] = [
                                    'driver_id' => $availableDriver['id'],
                                    'type' => $availableDriver['type']
                                ];
                                $driverCounts[$availableDriver['id']]['count']++;
                                $unitShiftCounts[$date][$unitId]++;
                                Log::info("Assigned driver {$availableDriver['id']} to unit $unitId on $date shift $planShiftKey");
                            }
                        }
                    }
                }

                // Check driver limits and redistribute if needed
                $shiftMap = [
                    'pagi' => 'morning',
                    'siang' => 'afternoon'
                ];
                
                foreach (['pagi', 'siang'] as $shift) {
                    $planShiftKey = $shiftMap[$shift];
                    
                    if (isset($shifts[$planShiftKey]['driver_id'])) {
                        $driverId = $shifts[$planShiftKey]['driver_id'];
                        $driverType = $shifts[$planShiftKey]['type'];
                        
                        if ($driverType === 'batangan' && $driverCounts[$driverId]['count'] > 14) {
                            // Find another batangan driver who:
                            // 1. Has fewer than 14 shifts
                            // 2. Is qualified for this unit
                            // 3. Is not already assigned that day
                            $replacement = $this->findReplacementDriver(
                                $unitId,
                                $date,
                                $shift,
                                'batangan',
                                $schedulePlan,
                                $driverCounts
                            );
                            
                            if ($replacement) {
                                $schedulePlan[$date][$unitId][$planShiftKey]['driver_id'] = $replacement['id'];
                                $driverCounts[$driverId]['count']--;
                                $driverCounts[$replacement['id']]['count']++;
                                Log::info("Replaced driver $driverId with {$replacement['id']} for unit $unitId on $date shift $planShiftKey - batangan");
                            }
                        } elseif ($driverType === 'cadangan' && $driverCounts[$driverId]['count'] > 11) {
                            // Find another cadangan driver who:
                            // 1. Has fewer than 11 shifts
                            // 2. Is qualified for this unit
                            // 3. Is not already assigned that day
                            $replacement = $this->findReplacementDriver(
                                $unitId,
                                $date,
                                $shift,
                                'cadangan',
                                $schedulePlan,
                                $driverCounts
                            );
                            
                            if ($replacement) {
                                $schedulePlan[$date][$unitId][$planShiftKey]['driver_id'] = $replacement['id'];
                                $driverCounts[$driverId]['count']--;
                                $driverCounts[$replacement['id']]['count']++;
                                Log::info("Replaced driver $driverId with {$replacement['id']} for unit $unitId on $date shift $planShiftKey - cadangan");
                            }
                        }
                    }
                }
            }
        }

        return $schedulePlan;
    }

    public function createSchedulesFromPlan(array $schedulePlan): void
    {
        DB::beginTransaction();
        try {
            $count = 0;
            $errors = [];

            // Log what we're working with
            $totalShifts = 0;
            foreach ($schedulePlan as $date => $unitPlans) {
                foreach ($unitPlans as $unitId => $shifts) {
                    foreach (['morning', 'afternoon'] as $shiftKey) {
                        if (!empty($shifts[$shiftKey]) && isset($shifts[$shiftKey]['driver_id'])) {
                            $totalShifts++;
                        }
                    }
                }
            }
            Log::info("Starting createSchedulesFromPlan with $totalShifts total shifts to process");

            foreach ($schedulePlan as $date => $unitPlans) {
                foreach ($unitPlans as $unitId => $shifts) {
                    $unit = Unit::find($unitId);
                    if (!$unit) {
                        Log::error("Unit ID $unitId not found in database");
                        $errors[] = "Unit ID $unitId not found";
                        continue;
                    }
                    
                    if ($unit->routes->isEmpty()) {
                        Log::error("Unit $unitId has no routes assigned");
                        $errors[] = "Unit $unitId has no routes";
                        continue;
                    }

                    $routeId = $unit->routes->first()->id;

                    // Map morning/afternoon to pagi/siang
                    $shiftMap = [
                        'morning' => 'pagi',
                        'afternoon' => 'siang'
                    ];

                    foreach ($shiftMap as $planKey => $dbShift) {
                        if (!empty($shifts[$planKey]) && isset($shifts[$planKey]['driver_id'])) {
                            try {
                                $driverId = $shifts[$planKey]['driver_id'];
                                
                                // Verify the driver exists
                                $driver = Driver::find($driverId);
                                if (!$driver) {
                                    Log::error("Driver ID $driverId not found in database");
                                    $errors[] = "Driver ID $driverId not found";
                                    continue;
                                }

                                Schedule::create([
                                    'unit_id' => $unitId,
                                    'driver_id' => $driverId,
                                    'route_id' => $routeId,
                                    'schedule_date' => $date,
                                    'shift' => $dbShift,
                                    'status' => 'active'
                                ]);
                                
                                $count++;
                                Log::info("Created schedule: unit $unitId, driver $driverId, date $date, shift $dbShift");
                            } catch (\Exception $e) {
                                Log::error("Error creating schedule: " . $e->getMessage(), [
                                    'unit_id' => $unitId,
                                    'driver_id' => $shifts[$planKey]['driver_id'] ?? 'unknown',
                                    'date' => $date,
                                    'shift' => $dbShift
                                ]);
                                $errors[] = "Error with unit $unitId: " . $e->getMessage();
                            }
                        }
                    }
                }
            }
            
            if ($count > 0) {
                DB::commit();
                Log::info("Successfully created $count schedules");
            } else {
                DB::rollBack();
                Log::error("No schedules were created. Rolling back transaction.", ['errors' => $errors]);
                throw new \Exception("Failed to create any schedules: " . implode(", ", $errors));
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error creating schedules: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            throw $e;
        }
    }
    
    private function findBestDriver(
        array $unitDrivers,
        string $type,
        string $shift,
        array $driverScheduleCounts,
        array $lastShiftByDriver,
        array $settings,
        array $dailyDriverAssignments = [],
        string $currentDate = null,
        array $driverShiftHistory = []
    ): ?int {
        Log::info("Finding best $type driver for $shift shift", [
            'availableDrivers' => $unitDrivers,
            'driverCounts' => $driverScheduleCounts,
            'settings' => $settings
        ]);
        
        $candidates = [];
        
        foreach ($unitDrivers as $driverId => $driverType) {
            if ($driverType !== $type || !isset($driverScheduleCounts[$driverId])) {
                continue;
            }
            
            // Skip if driver is already assigned on this day
            if ($currentDate && isset($dailyDriverAssignments[$currentDate][$driverId])) {
                Log::info("Driver $driverId already assigned on $currentDate, skipping");
                continue;
            }
            
            $stats = $driverScheduleCounts[$driverId];
            $maxSchedules = $settings['max_schedules'] ?? ($type === 'batangan' ? 14 : 11);
            $maxConsecutiveSameShift = $settings['max_consecutive_same_shift'] ?? 2;
            
            // Skip if over total limit
            if ($stats['total'] >= $maxSchedules) {
                continue;
            }
            
            // Get yesterday's and day before yesterday's dates
            $yesterdayDate = null;
            $dayBeforeYesterdayDate = null;
            if ($currentDate) {
                $yesterdayDate = Carbon::parse($currentDate)->subDay()->format('Y-m-d');
                $dayBeforeYesterdayDate = Carbon::parse($currentDate)->subDays(2)->format('Y-m-d');
            }
            
            // Implement shift sequence rules:
            // 1. If yesterday was 'Pagi', today can be 'Pagi' or 'Siang'
            // 2. If yesterday was 'Siang', today should be 'Siang', cannot assign 'Pagi' shift
            // 3. If yesterday was 'Siang', and no shift today, tomorrow can be either 'Pagi' or 'Siang'
            
            // Check if driver had a shift yesterday
            $hadShiftYesterday = false;
            $yesterdayShift = null;
            
            // Check if driver had a shift day before yesterday
            $hadShiftDayBeforeYesterday = false;
            $dayBeforeYesterdayShift = null;
            
            if (!empty($driverShiftHistory[$driverId])) {
                foreach ($driverShiftHistory[$driverId] as $historyEntry) {
                    if ($historyEntry['date'] === $yesterdayDate) {
                        $hadShiftYesterday = true;
                        $yesterdayShift = $historyEntry['shift'];
                    }
                    if ($historyEntry['date'] === $dayBeforeYesterdayDate) {
                        $hadShiftDayBeforeYesterday = true;
                        $dayBeforeYesterdayShift = $historyEntry['shift'];
                    }
                }
            }
            
            // Rule 2: If yesterday was 'Siang', today should be 'Siang', cannot assign 'Pagi' shift
            if ($hadShiftYesterday && $yesterdayShift === 'siang' && $shift === 'pagi') {
                Log::info("Driver $driverId had 'Siang' shift yesterday, cannot assign 'Pagi' today");
                continue;
            }
            
            // Rule 3: If day before yesterday was 'Siang', yesterday no shift, today can be either 'Pagi' or 'Siang'
            // This is already handled by not having any restriction in this case
            
            // Skip if too many consecutive afternoon shifts
            if ($shift === 'siang' && $stats['afternoon'] >= $maxConsecutiveSameShift && $lastShiftByDriver[$driverId] === 'siang') {
                continue;
            }
            
            // Skip if too many consecutive morning shifts
            if ($shift === 'pagi' && $stats['morning'] >= $maxConsecutiveSameShift && $lastShiftByDriver[$driverId] === 'pagi') {
                continue;
            }
            
            $score = $this->scoreDriverOption($driverId, $shift, $driverScheduleCounts, $lastShiftByDriver);
            $candidates[$driverId] = $score;
        }
        
        if (empty($candidates)) {
            return null;
        }
        
        arsort($candidates);
        return array_key_first($candidates);
    }
    
    private function scoreDriverOption(
        int $driverId,
        string $shift,
        array $driverScheduleCounts,
        array $lastShiftByDriver
    ): float {
        $score = 100;
        $stats = $driverScheduleCounts[$driverId];
        $shiftDiff = abs($stats['morning'] - $stats['afternoon']);
        $score -= ($shiftDiff * 15);
        $score -= ($stats['total'] * 5);
        
        if ($lastShiftByDriver[$driverId] === null) {
            $score += 30;
        }
        
        if ($lastShiftByDriver[$driverId] !== $shift) {
            $score += 20;
        }
        
        if ($shift === 'pagi' && $stats['morning'] < $stats['afternoon']) {
            $score += 25; // Higher bonus for reducing imbalance
        } elseif ($shift === 'siang' && $stats['afternoon'] < $stats['morning']) {
            $score += 25;
        }
        
        return $score;
    }

    private function findAvailableDriverForShift(
        int $unitId, 
        string $date,
        string $shift,
        array $schedulePlan,
        array $driverCounts
    ): ?array {
        $availableDrivers = [];
        
        foreach ($driverCounts as $driverId => $info) {
            // Skip if driver is already assigned on this date and shift
            if (isset($schedulePlan[$date][$unitId][$shift]['driver_id']) 
                && $schedulePlan[$date][$unitId][$shift]['driver_id'] === $driverId) {
                continue;
            }
            
            // Check if driver is under their limit
            $maxShifts = $info['type'] === 'batangan' ? 14 : 11;
            if ($info['count'] < $maxShifts && in_array($unitId, $info['units'])) {
                $availableDrivers[] = [
                    'id' => $driverId,
                    'driver_id' => $driverId,
                    'type' => $info['type'],
                    'count' => $info['count']
                ];
            }
        }
        
        // Sort by least assigned shifts
        usort($availableDrivers, function($a, $b) {
            return $a['count'] <=> $b['count'];
        });
        
        return !empty($availableDrivers) ? $availableDrivers[0] : null;
    }

    private function findReplacementDriver(
        int $unitId,
        string $date,
        string $shift,
        string $type,
        array $schedulePlan,
        array $driverCounts
    ): ?array {
        $candidates = [];
        
        foreach ($driverCounts as $driverId => $info) {
            // Only consider drivers of the same type with fewer shifts
            if ($info['type'] === $type && $info['count'] < ($type === 'batangan' ? 14 : 11)) {
                // Skip if driver is already assigned on this date and shift
                if (isset($schedulePlan[$date][$unitId][$shift]['driver_id']) 
                    && $schedulePlan[$date][$unitId][$shift]['driver_id'] === $driverId) {
                    continue;
                }
                
                // Check if driver is authorized for this unit
                if (in_array($unitId, $info['units'])) {
                    $candidates[] = [
                        'id' => $driverId,
                        'driver_id' => $driverId,
                        'type' => $type,
                        'count' => $info['count']
                    ];
                }
            }
        }
        
        // Sort by least assigned shifts
        usort($candidates, function($a, $b) {
            return $a['count'] <=> $b['count'];
        });
        
        return !empty($candidates) ? $candidates[0] : null;
    }

    private function balanceShifts(array &$schedulePlan, array &$driverScheduleCounts): void
    {
        foreach ($schedulePlan as $date => $unitPlans) {
            foreach ($unitPlans as $unitId => $shifts) {
                foreach (['pagi', 'siang'] as $shift) {
                    if (!isset($shifts[$shift]['driver_id'])) {
                        continue;
                    }

                    $driverId = $shifts[$shift]['driver_id'];
                    if (!isset($driverScheduleCounts[$driverId])) {
                        continue;
                    }
                    
                    $driverStats = $driverScheduleCounts[$driverId];
                    
                    // Check for significant imbalance (more than 2 shifts difference)
                    if ($shift === 'pagi' && $driverStats['morning'] > $driverStats['afternoon'] + 2) {
                        // Look for a driver with more afternoon shifts to swap with
                        foreach ($driverScheduleCounts as $otherDriverId => $otherStats) {
                            if ($otherDriverId === $driverId) continue;
                            
                            if ($otherStats['afternoon'] > $otherStats['morning'] 
                                && isset($shifts['siang']['driver_id'])
                                && $shifts['siang']['driver_id'] !== $otherDriverId) {
                                
                                // Swap the shifts
                                $tempMorning = $shifts['pagi'];
                                $schedulePlan[$date][$unitId]['pagi'] = [
                                    'driver_id' => $otherDriverId,
                                    'type' => $driverScheduleCounts[$otherDriverId]['type']
                                ];
                                
                                // Update counts
                                $driverScheduleCounts[$driverId]['morning']--;
                                $driverScheduleCounts[$otherDriverId]['morning']++;
                                break;
                            }
                        }
                    } elseif ($shift === 'siang' && $driverStats['afternoon'] > $driverStats['morning'] + 2) {
                        // Look for a driver with more morning shifts to swap with
                        foreach ($driverScheduleCounts as $otherDriverId => $otherStats) {
                            if ($otherDriverId === $driverId) continue;
                            
                            if ($otherStats['morning'] > $otherStats['afternoon']
                                && isset($shifts['pagi']['driver_id'])
                                && $shifts['pagi']['driver_id'] !== $otherDriverId) {
                                
                                // Swap the shifts
                                $tempAfternoon = $shifts['siang'];
                                $schedulePlan[$date][$unitId]['siang'] = [
                                    'driver_id' => $otherDriverId,
                                    'type' => $driverScheduleCounts[$otherDriverId]['type']
                                ];
                                
                                // Update counts
                                $driverScheduleCounts[$driverId]['afternoon']--;
                                $driverScheduleCounts[$otherDriverId]['afternoon']++;
                                break;
                            }
                        }
                    }
                }
            }
        }
    }
}