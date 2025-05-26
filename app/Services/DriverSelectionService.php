<?php

namespace App\Services;

use App\Models\Driver;
use App\Models\Unit;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class DriverSelectionService
{
    public function findBatanganDriverForUnit(
        Unit $unit,
        string $dateStr,
        string $shift,
        array $scheduledDriverIdsSet,
        array $driversOnLeaveIdsSet,
        array $previousDaySchedules,
        array $twoDaysAgoSchedules,
        array $driverScheduleCounts,
        Collection $fixedDrivers,
        array $unitAssignmentsCache,
        array $unitDayOffs,
        array $batanganSettings,
        array $cadanganSettings,
        array &$messages
    ): ?Driver {
        if ($unit->routes->isEmpty()) {
            $messages[] = "Unit {$unit->unit_number} has no routes assigned, skipping.";
            return null;
        }

        $routeId = $unit->routes->first()->id;

        if (isset($unitDayOffs[$dateStr]) && in_array($unit->id, $unitDayOffs[$dateStr])) {
            $messages[] = "Unit {$unit->unit_number} is marked as unavailable in unit_renops for {$dateStr}, skipping.";
            Log::info("Unit {$unit->unit_number} (ID: {$unit->id}) is marked as unavailable in unit_renops for {$dateStr}");
            return null;
        }

        $unitBatanganDrivers = $fixedDrivers->filter(function($driver) use ($unit, $unitAssignmentsCache) {
            return isset($unitAssignmentsCache[$unit->id]) && 
                   isset($unitAssignmentsCache[$unit->id][$driver->id]);
        });

        if (!$unitBatanganDrivers->isEmpty()) {
            $driver = $this->filterAndFindSuitableDriver(
                $unitBatanganDrivers,
                $unit,
                $dateStr,
                $shift,
                $scheduledDriverIdsSet,
                $driversOnLeaveIdsSet,
                $previousDaySchedules,
                $twoDaysAgoSchedules,
                $driverScheduleCounts,
                $batanganSettings,
                $cadanganSettings,
                true, // Strict unit assignment for batangan drivers
                $messages
            );

            if ($driver) {
                $messages[] = "Assigned batangan driver {$driver->name} to unit {$unit->unit_number} for {$dateStr} {$shift} (unit-specific assignment)";
                return $driver;
            }
        }

        return null;
    }

    public function findCadanganDriverForUnit(
        Unit $unit,
        string $dateStr,
        string $shift,
        array $scheduledDriverIdsSet,
        array $driversOnLeaveIdsSet,
        array $previousDaySchedules,
        array $twoDaysAgoSchedules,
        array $driverScheduleCounts,
        Collection $nonFixedDrivers,
        array $unitAssignmentsCache,
        array $routeAssignmentsCache,
        array $unitDayOffs,
        array $batanganSettings,
        array $cadanganSettings,
        array &$messages
    ): ?Driver {
        if ($unit->routes->isEmpty()) {
            $messages[] = "Unit {$unit->unit_number} has no routes assigned, skipping.";
            return null;
        }

        $routeId = $unit->routes->first()->id;

        if (isset($unitDayOffs[$dateStr]) && in_array($unit->id, $unitDayOffs[$dateStr])) {
            $messages[] = "Unit {$unit->unit_number} is marked as unavailable in unit_renops for {$dateStr}, skipping.";
            return null;
        }

        $unitCadanganDrivers = $nonFixedDrivers->filter(function($driver) use ($unit, $unitAssignmentsCache) {
            return isset($unitAssignmentsCache[$unit->id]) && 
                   isset($unitAssignmentsCache[$unit->id][$driver->id]);
        });

        if (!$unitCadanganDrivers->isEmpty()) {
            $driver = $this->filterAndFindSuitableDriver(
                $unitCadanganDrivers,
                $unit,
                $dateStr,
                $shift,
                $scheduledDriverIdsSet,
                $driversOnLeaveIdsSet,
                $previousDaySchedules,
                $twoDaysAgoSchedules,
                $driverScheduleCounts,
                $cadanganSettings,
                $batanganSettings,
                false, // No strict unit assignment for cadangan drivers
                $messages
            );

            if ($driver) {
                $messages[] = "Assigned cadangan driver {$driver->name} to unit {$unit->unit_number} for {$dateStr} {$shift} (unit-specific assignment)";
                return $driver;
            }
        }

        $routeQualifiedDrivers = $nonFixedDrivers->filter(function($driver) use ($routeId, $unitAssignmentsCache, $routeAssignmentsCache) {
            $routeQualified = isset($routeAssignmentsCache[$routeId]) && 
                             isset($routeAssignmentsCache[$routeId][$driver->id]);
            
            $hasUnitAssignments = false;
            foreach ($unitAssignmentsCache as $unitDrivers) {
                if (isset($unitDrivers[$driver->id])) {
                    $hasUnitAssignments = true;
                    break;
                }
            }
            
            return $routeQualified && $hasUnitAssignments;
        });

        if (!$routeQualifiedDrivers->isEmpty()) {
            $driver = $this->filterAndFindSuitableDriver(
                $routeQualifiedDrivers,
                $unit,
                $dateStr,
                $shift,
                $scheduledDriverIdsSet,
                $driversOnLeaveIdsSet,
                $previousDaySchedules,
                $twoDaysAgoSchedules,
                $driverScheduleCounts,
                $cadanganSettings,
                $batanganSettings,
                false, // No strict unit assignment for cadangan drivers
                $messages
            );

            if ($driver) {
                $messages[] = "Assigned cadangan driver {$driver->name} to unit {$unit->unit_number} for {$dateStr} {$shift} (route-qualified)";
                return $driver;
            }
        }

        $messages[] = "No suitable cadangan driver found for unit {$unit->unit_number} for {$dateStr} {$shift}";
        return null;
    }

    protected function filterAndFindSuitableDriver(
        Collection $drivers,
        Unit $unit,
        string $dateStr,
        string $shift,
        array $scheduledDriverIdsSet,
        array $driversOnLeaveIdsSet,
        array $previousDaySchedules,
        array $twoDaysAgoSchedules,
        array $driverScheduleCounts,
        array $driverSettings,
        array $otherTypeSettings,
        bool $strictUnitAssignment = false,
        array &$messages
    ): ?Driver {
        // Filter out already scheduled drivers
        $availableDrivers = $drivers->filter(function ($driver) use ($scheduledDriverIdsSet) {
            return !isset($scheduledDriverIdsSet[$driver->id]);
        });

        if ($availableDrivers->isEmpty()) {
            return null;
        }

        // Filter out drivers on leave
        $availableDrivers = $availableDrivers->filter(function ($driver) use ($driversOnLeaveIdsSet) {
            return !isset($driversOnLeaveIdsSet[$driver->id]);
        });

        // Check if it's a weekend and apply the weekend coverage ratio for batangan drivers
        $currentDate = Carbon::parse($dateStr);
        if ($currentDate->isWeekend() && $drivers->first()->type === 'batangan') {
            $totalBatanganDrivers = $drivers->count();
            $weekendLimit = ceil($totalBatanganDrivers * ($driverSettings['weekend_ratio'] ?? 0.8));
            
            if (count($availableDrivers) > $weekendLimit) {
                // Randomly select 80% of drivers for weekend shifts
                $availableDrivers = $availableDrivers->random($weekendLimit);
            }
        }

        if ($availableDrivers->isEmpty()) {
            return null;
        }

        $currentDate = Carbon::parse($dateStr);
        $dayOfMonth = $currentDate->day;
        $isFirstPeriod = $dayOfMonth <= 15;
        
        // Apply more restrictive limits for cadangan drivers
        $availableDrivers = $availableDrivers->filter(function ($driver) use ($driverScheduleCounts, $driverSettings, $otherTypeSettings) {
            $currentCount = $driverScheduleCounts[$driver->id] ?? 0;
            $maxSchedules = $driverSettings['max_schedules'];
            
            // For cadangan drivers, apply a dynamic cap based on the batangan driver schedules
            if ($driver->type === 'cadangan') {
                // Calculate the target ratio - cadangan drivers should have fewer schedules
                // For example, if batangan max is 13, cadangan could be 10-11 (approximately 85%)
                $batanganMax = $otherTypeSettings['max_schedules'];
                $cadanganRatio = 0.85; // cadangan drivers get 85% of batangan driver schedules
                $adjustedMax = (int)floor($batanganMax * $cadanganRatio);
                
                // Use the lower of the two max values
                $maxSchedules = min($maxSchedules, $adjustedMax);
            }
            
            return $currentCount < $maxSchedules;
        });

        if ($availableDrivers->isEmpty()) {
            return null;
        }

        $availableDrivers = $availableDrivers->filter(function ($driver) use ($previousDaySchedules, $twoDaysAgoSchedules, $shift) {
            $hadYesterdaySchedule = isset($previousDaySchedules[$driver->id]);
            $hadTwoDaysAgoSchedule = isset($twoDaysAgoSchedules[$driver->id]);
            if ($hadYesterdaySchedule) {
                $yesterdayShift = $previousDaySchedules[$driver->id]['shift'];
                if ($yesterdayShift === 'siang' && $shift === 'pagi') {
                    return false;
                }
                
                if ($shift === 'siang' && $yesterdayShift === 'siang' && $hadTwoDaysAgoSchedule) {
                    $twoDaysAgoShift = $twoDaysAgoSchedules[$driver->id]['shift'];
                    if ($twoDaysAgoShift === 'siang') {
                        return false;
                    }
                }
            }
            
            return true;
        });

        if ($availableDrivers->isEmpty()) {
            return null;
        }

        // Sort drivers by count but prioritize based on driver type
        $sortedDrivers = $availableDrivers->sort(function ($driverA, $driverB) use ($driverScheduleCounts) {
            $countA = $driverScheduleCounts[$driverA->id] ?? 0;
            $countB = $driverScheduleCounts[$driverB->id] ?? 0;
            
            // If driver A is batangan and driver B is cadangan, prioritize A (batangan)
            if ($driverA->type === 'batangan' && $driverB->type === 'cadangan') {
                return -1; // A comes first (batangan has priority)
            }
            
            // If driver A is cadangan and driver B is batangan, prioritize B (batangan)
            if ($driverA->type === 'cadangan' && $driverB->type === 'batangan') {
                return 1; // B comes first (batangan has priority)
            }
            
            // If same type, use schedule count to prioritize those with fewer schedules
            return $countA <=> $countB;
        });

        return $sortedDrivers->first();
    }
}
