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
        $availableDrivers = $drivers->filter(function ($driver) use ($scheduledDriverIdsSet) {
            return !isset($scheduledDriverIdsSet[$driver->id]);
        });

        if ($availableDrivers->isEmpty()) {
            return null;
        }

        $availableDrivers = $availableDrivers->filter(function ($driver) use ($driversOnLeaveIdsSet) {
            return !isset($driversOnLeaveIdsSet[$driver->id]);
        });

        if ($availableDrivers->isEmpty()) {
            return null;
        }

        $currentDate = Carbon::parse($dateStr);
        $dayOfMonth = $currentDate->day;
        $isFirstPeriod = $dayOfMonth <= 15;
        
        $availableDrivers = $availableDrivers->filter(function ($driver) use ($driverScheduleCounts, $driverSettings) {
            $currentCount = $driverScheduleCounts[$driver->id] ?? 0;
            $maxSchedules = $driverSettings['max_schedules'];
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

        $sortedDrivers = $availableDrivers->sortBy(function ($driver) use ($driverScheduleCounts) {
            return $driverScheduleCounts[$driver->id] ?? 0;
        });

        return $sortedDrivers->first();
    }
}
