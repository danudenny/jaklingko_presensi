<?php

namespace App\Services;

use App\Models\Driver;
use App\Models\DriverHistory;
use App\Models\DriverScheduleHistory;
use App\Models\Route;
use App\Models\Schedule;
use App\Models\Unit;
use App\Models\Holiday;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

    /**
     * Generate schedules for a specific date range
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function generateSchedules(string $startDate, string $endDate): array
    {
        $startDate = Carbon::parse($startDate);
        $endDate = Carbon::parse($endDate);
        
        $results = [
            'success' => 0,
            'failed' => 0,
            'messages' => []
        ];

        // Load all required data
        $this->loadData($results);
        
        if ($this->units->isEmpty()) {
            $results['messages'][] = "No units available for scheduling. Cannot generate schedules.";
            return $results;
        }

        if ($this->fixedDrivers->isEmpty() && $this->nonFixedDrivers->isEmpty()) {
            $results['messages'][] = "No drivers available for scheduling. Cannot generate schedules.";
            return $results;
        }
        
        // Process each day in the date range
        $currentDate = $startDate->copy();
        while ($currentDate->lte($endDate)) {
            $dateStr = $currentDate->format('Y-m-d');
            $dayOfWeek = $currentDate->dayOfWeek; // 0 (Sunday) to 6 (Saturday)
            
            // Reset weekend drivers pool for each day
            $this->weekendDriversPool = [];
            
            // Determine resource percentage based on day type
            $resourcePercentage = $this->getResourcePercentage($currentDate);
            
            // Check if it's a weekend or holiday
            $isWeekend = ($dayOfWeek == 0 || $dayOfWeek == 6);
            $isHoliday = Holiday::whereDate('date', $dateStr)->exists();
            
            // Prepare drivers pool if it's a weekend or holiday
            if ($isWeekend || $isHoliday) {
                $this->prepareRestrictedDriversPool($currentDate, $resourcePercentage);
            }
            
            $results['messages'][] = "Processing date: {$dateStr}, Day: {$currentDate->format('l')}, Resource allocation: {$resourcePercentage}%";
            
            // Process morning shifts
            $this->generateShiftSchedules($dateStr, 'pagi', $resourcePercentage, $results, $startDate->format('Y-m-d'), $endDate->format('Y-m-d'));
            
            // Process evening shifts
            $this->generateShiftSchedules($dateStr, 'siang', $resourcePercentage, $results, $startDate->format('Y-m-d'), $endDate->format('Y-m-d'));
            
            $currentDate->addDay();
        }
        
        $this->balanceSchedulesInPeriod($startDate, $endDate, $results);
        
        $results['success'] = $this->success;
        $results['failed'] = $this->failed;
        $results['messages'] = $this->messages;
        
        return $results;
    }

    /**
     * Load all required data for scheduling
     *
     * @param array $results
     * @return void
     */
    protected function loadData(array &$results): void
    {
        // Load active units
        $this->units = Unit::active()->get();
        if ($this->units->isEmpty()) {
            // Try with the first status we find in the database
            $firstStatus = Unit::first() ? Unit::first()->status : null;
            if ($firstStatus) {
                $this->units = Unit::where('status', $firstStatus)->get();
                $this->messages[] = "Using units with status '{$firstStatus}' instead of 'active'";
            }
        }
        
        // Load active routes
        $this->routes = Route::active()->get();
        if ($this->routes->isEmpty() && Route::exists()) {
            $this->routes = Route::all();
            $this->messages[] = "Using all routes regardless of status";
        }
        
        // Load fixed drivers (batangan/D)
        $this->fixedDrivers = Driver::batangan()->active()->get();
        
        // Load non-fixed drivers (cadangan/DD)
        $this->nonFixedDrivers = Driver::cadangan()->active()->get();
        
        $this->messages[] = "Loaded {$this->units->count()} units, {$this->routes->count()} routes, {$this->fixedDrivers->count()} fixed drivers, and {$this->nonFixedDrivers->count()} non-fixed drivers";
    }
    
    /**
     * Determine resource percentage based on day type
     * Weekdays: 100%
     * Saturday: 80%
     * Sunday: 70%
     * Holiday: 80%
     *
     * @param Carbon $date
     * @return int
     */
    protected function getResourcePercentage(Carbon $date): int
    {
        $dayOfWeek = $date->dayOfWeek;
        
        // Check if it's a holiday
        $isHoliday = Holiday::whereDate('date', $date->format('Y-m-d'))->exists();
        
        if ($isHoliday) {
            $this->messages[] = "Date {$date->format('Y-m-d')} is a holiday, using 80% resource allocation";
            return 80; // 80% for holidays
        }
        
        if ($dayOfWeek == 6) { // Saturday
            return 80; // 80% for Saturday
        }
        
        if ($dayOfWeek == 0) { // Sunday
            return 70; // 70% for Sunday
        }
        
        return 100; // 100% for weekdays
    }
    
    /**
     * Generate schedules for a specific date and shift
     *
     * @param string $date
     * @param string $shift
     * @param int $resourcePercentage
     * @param array $results
     * @param string $periodStartDate
     * @param string $periodEndDate
     * @return void
     */
    protected function generateShiftSchedules(string $date, string $shift, int $resourcePercentage, array &$results, string $periodStartDate, string $periodEndDate): void
    {
        $carbonDate = Carbon::parse($date);
        $month = $carbonDate->month;
        $year = $carbonDate->year;
        
        // Get existing schedules for this date and shift
        $existingScheduledUnits = Schedule::where('schedule_date', $date)
            ->where('shift', $shift)
            ->pluck('unit_id')
            ->toArray();
        
        $this->messages[] = "Found " . count($existingScheduledUnits) . " existing schedules for $date ($shift)";
        
        // Only process units that don't have schedules yet
        $unitsToSchedule = $this->units->filter(function ($unit) use ($existingScheduledUnits) {
            return !in_array($unit->id, $existingScheduledUnits);
        });
        
        // Apply resource percentage if not 100%
        if ($resourcePercentage < 100) {
            $unitCount = $unitsToSchedule->count();
            $unitsToUse = ceil($unitCount * $resourcePercentage / 100);
            $unitsToSchedule = $unitsToSchedule->take($unitsToUse);
            $this->messages[] = "Using {$resourcePercentage}% of units: {$unitsToUse} of {$unitCount}";
        }
        
        foreach ($unitsToSchedule as $unit) {
            // Find suitable driver for this unit
            $assignedDriver = $this->findSuitableDriver($date, $shift, $unit, $periodStartDate, $periodEndDate);
            
            if ($assignedDriver) {
                try {
                    DB::beginTransaction();
                    
                    // Find a suitable route for this unit
                    $route = $this->findSuitableRoute($unit);
                    
                    if (!$route) {
                        $this->messages[] = "No suitable route found for unit {$unit->unit_number}, using first available route";
                        $route = $this->routes->first();
                    }
                    
                    // Create schedule
                    $schedule = Schedule::create([
                        'driver_id' => $assignedDriver->id,
                        'unit_id' => $unit->id,
                        'route_id' => $route->id,
                        'schedule_date' => $date,
                        'shift' => $shift,
                        'status' => 'scheduled',
                    ]);
                    
                    // Create driver history
                    DriverHistory::create([
                        'driver_id' => $assignedDriver->id,
                        'unit_id' => $unit->id,
                        'shift' => $shift,
                        'start_date' => $date,
                        'end_date' => $date,
                        'as_backup' => false,
                        'as_renops' => false,
                        'on_leave' => false,
                        'on_duty' => true,
                    ]);
                    
                    // Update driver schedule history for the period
                    DriverScheduleHistory::incrementScheduleCount(
                        $assignedDriver->id,
                        $periodStartDate,
                        $periodEndDate
                    );
                    
                    DB::commit();
                    $this->success++;
                    
                    $driverType = $assignedDriver->type === 'batangan' ? 'Fixed Driver' : 'Non-Fixed Driver';
                    $this->messages[] = "Created schedule for {$assignedDriver->name} ({$driverType}) on {$date} ({$shift}) with unit {$unit->unit_number}";
                    
                } catch (\Exception $e) {
                    DB::rollBack();
                    $this->failed++;
                    $this->messages[] = "Failed to create schedule for unit {$unit->unit_number}: " . $e->getMessage();
                }
            } else {
                $this->failed++;
                $this->messages[] = "No suitable driver found for unit {$unit->unit_number} on {$date} ({$shift})";
            }
        }
    }
    
    /**
     * Find a suitable driver for a unit on a specific date and shift
     *
     * @param string $date
     * @param string $shift
     * @param Unit $unit
     * @param string $periodStartDate
     * @param string $periodEndDate
     * @return Driver|null
     */
    protected function findSuitableDriver(string $date, string $shift, Unit $unit, string $periodStartDate, string $periodEndDate): ?Driver
    {
        $carbonDate = Carbon::parse($date);
        $month = $carbonDate->month;
        $year = $carbonDate->year;
        $dayOfWeek = $carbonDate->dayOfWeek;
        
        // Check if it's a holiday
        $isHoliday = Holiday::whereDate('date', $date)->exists();
        
        // Get qualified fixed drivers (D) for this unit
        $qualifiedFixedDrivers = $this->fixedDrivers->filter(function ($driver) use ($unit) {
            return $driver->units->contains($unit->id);
        });
        
        // Get qualified non-fixed drivers (DD) for this unit
        $qualifiedNonFixedDrivers = $this->nonFixedDrivers->filter(function ($driver) use ($unit) {
            return $driver->units->contains($unit->id);
        });
        
        // Apply resource restrictions if it's a weekend or holiday
        if ($dayOfWeek == 6 || $dayOfWeek == 0 || $isHoliday) {
            // Filter fixed drivers to only those in the restricted pool
            $qualifiedFixedDrivers = $qualifiedFixedDrivers->filter(function ($driver) {
                return in_array($driver->id, $this->weekendDriversPool['fixed']);
            });
            
            // Filter non-fixed drivers to only those in the restricted pool
            $qualifiedNonFixedDrivers = $qualifiedNonFixedDrivers->filter(function ($driver) {
                return in_array($driver->id, $this->weekendDriversPool['non_fixed']);
            });
            
            if ($isHoliday) {
                $this->messages[] = "Applying holiday resource restrictions for {$date}";
            } else {
                $dayName = ($dayOfWeek == 6) ? 'Saturday' : 'Sunday';
                $this->messages[] = "Applying {$dayName} resource restrictions for {$date}";
            }
        }
        
        // Filter available drivers
        $availableFixedDrivers = $this->filterAvailableDrivers($qualifiedFixedDrivers, $date, $shift);
        $availableNonFixedDrivers = $this->filterAvailableDrivers($qualifiedNonFixedDrivers, $date, $shift);
        
        // Sort by schedule count in the current period to ensure fair distribution
        $availableFixedDrivers = $this->sortDriversByPeriodScheduleCount($availableFixedDrivers, $periodStartDate, $periodEndDate);
        $availableNonFixedDrivers = $this->sortDriversByPeriodScheduleCount($availableNonFixedDrivers, $periodStartDate, $periodEndDate);
        
        // Prioritize drivers who haven't met their target
        $availableFixedDrivers = $this->prioritizeDriversBelowTarget($availableFixedDrivers, $periodStartDate, $periodEndDate);
        $availableNonFixedDrivers = $this->prioritizeDriversBelowTarget($availableNonFixedDrivers, $periodStartDate, $periodEndDate);
        
        // Try to assign a fixed driver first
        if ($availableFixedDrivers->isNotEmpty()) {
            return $availableFixedDrivers->first();
        }
        
        // If no fixed driver available, try a non-fixed driver
        if ($availableNonFixedDrivers->isNotEmpty()) {
            return $availableNonFixedDrivers->first();
        }
        
        // No suitable driver found
        return null;
    }
    
    /**
     * Prioritize drivers who haven't met their target for the period
     *
     * @param Collection $drivers
     * @param string $periodStartDate
     * @param string $periodEndDate
     * @return Collection
     */
    protected function prioritizeDriversBelowTarget(Collection $drivers, string $periodStartDate, string $periodEndDate): Collection
    {
        if ($drivers->isEmpty()) {
            return $drivers;
        }
        
        // Get all driver IDs
        $driverIds = $drivers->pluck('id')->toArray();
        
        // Get schedule history for these drivers in the current period
        $scheduleHistories = DriverScheduleHistory::whereIn('driver_id', $driverIds)
            ->where('period_start_date', $periodStartDate)
            ->where('period_end_date', $periodEndDate)
            ->get()
            ->keyBy('driver_id');
        
        // Sort drivers by schedule count (prioritize those with fewer schedules)
        return $drivers->sortBy(function ($driver) use ($scheduleHistories, $periodStartDate, $periodEndDate) {
            // If driver has no history yet, create a default one
            if (!$scheduleHistories->has($driver->id)) {
                return 0; // Prioritize drivers with no schedules yet
            }
            
            $history = $scheduleHistories->get($driver->id);
            return $history->schedule_count;
        });
    }
    
    /**
     * Sort drivers by the number of schedules they have in the current period
     *
     * @param Collection $drivers
     * @param string $periodStartDate
     * @param string $periodEndDate
     * @return Collection
     */
    protected function sortDriversByPeriodScheduleCount(Collection $drivers, string $periodStartDate, string $periodEndDate): Collection
    {
        if ($drivers->isEmpty()) {
            return $drivers;
        }
        
        // Get all driver IDs
        $driverIds = $drivers->pluck('id')->toArray();
        
        // Get schedule history for these drivers in the current period
        $scheduleHistories = DriverScheduleHistory::whereIn('driver_id', $driverIds)
            ->where('period_start_date', $periodStartDate)
            ->where('period_end_date', $periodEndDate)
            ->get()
            ->keyBy('driver_id');
        
        // Sort drivers by schedule count (prioritize those with fewer schedules)
        return $drivers->sortBy(function ($driver) use ($scheduleHistories) {
            // If driver has no history yet, create a default one
            if (!$scheduleHistories->has($driver->id)) {
                return 0; // Prioritize drivers with no schedules yet
            }
            
            $history = $scheduleHistories->get($driver->id);
            return $history->schedule_count;
        });
    }
    
    /**
     * Filter drivers based on availability for a specific date and shift
     * 
     * Rules:
     * 1. If a driver had evening shift (SE) today, cannot have morning shift (SM) tomorrow
     * 2. In one day, a driver can only have one shift (either SM or SE)
     *
     * @param Collection $drivers
     * @param string $date
     * @param string $shift
     * @return Collection
     */
    protected function filterAvailableDrivers(Collection $drivers, string $date, string $shift): Collection
    {
        $carbonDate = Carbon::parse($date);
        $previousDay = $carbonDate->copy()->subDay()->format('Y-m-d');
        
        return $drivers->filter(function ($driver) use ($date, $shift, $previousDay) {
            // Check if driver is active
            if ($driver->status !== 'aktif') {
                return false;
            }
            
            // Check if driver is on leave
            $onLeave = $driver->leaveRequests()
                ->where('status', 'approved')
                ->where('start_date', '<=', $date)
                ->where('end_date', '>=', $date)
                ->exists();
                
            if ($onLeave) {
                return false;
            }
            
            // Check if already assigned to any shift on this date
            $alreadyAssigned = Schedule::where('driver_id', $driver->id)
                ->where('schedule_date', $date)
                ->exists();
                
            if ($alreadyAssigned) {
                return false;
            }
            
            // Check shift sequence constraint:
            // If previous day was evening shift (siang), cannot assign morning shift (pagi) today
            if ($shift === 'pagi') {
                $previousDayEvening = Schedule::where('driver_id', $driver->id)
                    ->where('schedule_date', $previousDay)
                    ->where('shift', 'siang')
                    ->exists();
                    
                if ($previousDayEvening) {
                    // Cannot assign morning shift after evening shift
                    return false;
                }
            }
            
            // Driver is available
            return true;
        });
    }
    
    /**
     * Find a suitable route for a unit
     *
     * @param Unit $unit
     * @return Route|null
     */
    protected function findSuitableRoute(Unit $unit): ?Route
    {
        // First check if the unit is associated with any routes
        $unitRoutes = $unit->routes;
        
        if ($unitRoutes->isNotEmpty()) {
            return $unitRoutes->first();
        }
        
        // If no specific routes for this unit, return the first active route
        return $this->routes->first();
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
     * Prepare weekend drivers pool based on resource percentage
     *
     * @param Carbon $date
     * @return void
     */
    protected function prepareRestrictedDriversPool(Carbon $date, int $resourcePercentage): void
    {
        $dayOfWeek = $date->dayOfWeek;
        $month = $date->month;
        $year = $date->year;
        
        // Prepare fixed drivers pool
        $totalFixedDrivers = $this->fixedDrivers->count();
        $fixedDriverLimit = ceil($totalFixedDrivers * $resourcePercentage / 100);
        
        // Prepare non-fixed drivers pool
        $totalNonFixedDrivers = $this->nonFixedDrivers->count();
        $nonFixedDriverLimit = ceil($totalNonFixedDrivers * $resourcePercentage / 100);
        
        // Sort drivers by assignment count to ensure fair distribution
        $sortedFixedDrivers = $this->sortDriversByAssignmentCount($this->fixedDrivers, $month, $year);
        $sortedNonFixedDrivers = $this->sortDriversByAssignmentCount($this->nonFixedDrivers, $month, $year);
        
        // Take only the percentage of drivers with fewest assignments
        $selectedFixedDrivers = $sortedFixedDrivers->take($fixedDriverLimit);
        $selectedNonFixedDrivers = $sortedNonFixedDrivers->take($nonFixedDriverLimit);
        
        // Store in the weekend drivers pool
        $this->weekendDriversPool = [
            'fixed' => $selectedFixedDrivers->pluck('id')->toArray(),
            'non_fixed' => $selectedNonFixedDrivers->pluck('id')->toArray()
        ];
        
        $dayName = ($dayOfWeek == 6) ? 'Saturday' : 'Sunday';
        $this->messages[] = "{$dayName}: Selected {$fixedDriverLimit} of {$totalFixedDrivers} fixed drivers and {$nonFixedDriverLimit} of {$totalNonFixedDrivers} non-fixed drivers for weekend pool ({$resourcePercentage}%)";
    }
    
    /**
     * Balance schedules in the 15-day period to ensure fair distribution
     * 
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param array $results
     * @return void
     */
    protected function balanceSchedulesInPeriod(Carbon $startDate, Carbon $endDate, array &$results): void
    {
        $periodStartDate = $startDate->format('Y-m-d');
        $periodEndDate = $endDate->format('Y-m-d');
        
        // Get all drivers
        $allDrivers = Driver::active()->get();
        
        // Check schedule distribution for all drivers
        foreach ($allDrivers as $driver) {
            // Get or create schedule history for this driver in this period
            $history = DriverScheduleHistory::firstOrCreate(
                [
                    'driver_id' => $driver->id,
                    'period_start_date' => $periodStartDate,
                    'period_end_date' => $periodEndDate,
                ],
                [
                    'schedule_count' => 0,
                    'target_count' => 14,
                    'target_met' => false,
                ]
            );
            
            // Count actual schedules for this driver in this period
            $actualCount = Schedule::where('driver_id', $driver->id)
                ->whereBetween('schedule_date', [$periodStartDate, $periodEndDate])
                ->count();
            
            // Update history if actual count differs
            if ($history->schedule_count != $actualCount) {
                $history->schedule_count = $actualCount;
                $history->target_met = $actualCount >= $history->target_count;
                $history->save();
                
                $this->messages[] = "Updated schedule count for driver {$driver->name}: {$actualCount} of {$history->target_count} target schedules";
            }
            
            // Log if driver is below target
            if (!$history->target_met) {
                $remaining = $history->target_count - $history->schedule_count;
                $this->messages[] = "Driver {$driver->name} has {$history->schedule_count} schedules, needs {$remaining} more to meet target of {$history->target_count}";
            }
        }
        
        // Get summary of drivers below target
        $driversBelowTarget = DriverScheduleHistory::where('period_start_date', $periodStartDate)
            ->where('period_end_date', $periodEndDate)
            ->where('target_met', false)
            ->count();
        
        if ($driversBelowTarget > 0) {
            $this->messages[] = "WARNING: {$driversBelowTarget} drivers haven't met their target of 14 schedules in this period";
        } else {
            $this->messages[] = "SUCCESS: All drivers have met their target of 14 schedules in this period";
        }
    }
}
