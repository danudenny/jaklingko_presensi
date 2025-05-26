<?php

namespace App\Services;

use App\Models\Driver;
use App\Models\DriverScheduleSettings;
use App\Models\Holiday;
use App\Models\Schedule;
use App\Models\Unit;
use App\Models\UnitRenops;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ScheduleGeneratorUtilityService
{
    public function preloadUnitDayOffs(Carbon $startDate, Carbon $endDate, array &$unitDayOffs): void
    {
        $unitDayOffs = [];
        
        $allUnitDayOffs = UnitRenops::whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get()
            ->groupBy(function ($item) {
                return $item->date->format('Y-m-d');
            });

        foreach ($allUnitDayOffs as $date => $entries) {
            $unitDayOffs[$date] = $entries->pluck('unit_id')->toArray();
        }
    }

    public function precacheUnitAssignments(
        Collection $units, 
        Collection $fixedDrivers, 
        Collection $nonFixedDrivers, 
        array &$unitAssignmentsCache
    ): void {
        $unitAssignmentsCache = [];
        
        foreach ($fixedDrivers as $driver) {
            foreach ($driver->units as $unit) {
                if (!isset($unitAssignmentsCache[$unit->id])) {
                    $unitAssignmentsCache[$unit->id] = [];
                }
                $unitAssignmentsCache[$unit->id][$driver->id] = true;
            }
        }
        
        foreach ($nonFixedDrivers as $driver) {
            foreach ($driver->units as $unit) {
                if (!isset($unitAssignmentsCache[$unit->id])) {
                    $unitAssignmentsCache[$unit->id] = [];
                }
                $unitAssignmentsCache[$unit->id][$driver->id] = true;
            }
        }
        
    }

    public function precacheRouteAssignments(
        Collection $routes, 
        Collection $fixedDrivers, 
        Collection $nonFixedDrivers, 
        array &$routeAssignmentsCache
    ): void {
        $routeAssignmentsCache = [];
        
        foreach ($fixedDrivers as $driver) {
            foreach ($driver->routes as $route) {
                if (!isset($routeAssignmentsCache[$route->id])) {
                    $routeAssignmentsCache[$route->id] = [];
                }
                $routeAssignmentsCache[$route->id][$driver->id] = true;
            }
        }
        
        foreach ($nonFixedDrivers as $driver) {
            foreach ($driver->routes as $route) {
                if (!isset($routeAssignmentsCache[$route->id])) {
                    $routeAssignmentsCache[$route->id] = [];
                }
                $routeAssignmentsCache[$route->id][$driver->id] = true;
            }
        }
        
    }

    public function precacheExistingSchedules(Carbon $startDate, Carbon $endDate, array &$existingSchedules): void
    {
        $existingSchedules = Schedule::whereBetween('schedule_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get()
            ->groupBy('schedule_date')
            ->toArray();
        
    }

    public function precacheLeaveRequests(Carbon $startDate, Carbon $endDate, array &$leaveRequests): void
    {
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
            ->get()
            ->toArray();
        
    }
    
    public function loadDriverScheduleSettings(
        array &$driverScheduleSettings, 
        &$batanganSettings, 
        &$cadanganSettings, 
        array &$messages
    ): void {
        $batanganSettingsObj = DriverScheduleSettings::getSettingsForType('batangan');
        if (!$batanganSettingsObj) {
            Log::warning("No settings found for driver type 'batangan' in the database. Using default values.");
            $driverScheduleSettings['batangan'] = [
                'min_schedules' => 13, // Target for fixed schedule drivers
                'max_schedules' => 14, // Maximum allowed per period
                'period_days' => 15,   // 15 day period
                'weekend_ratio' => 0.8 // 80% weekend coverage
            ];
            $messages[] = "Using default settings for batangan drivers: min 13, max 14, period 15 days, weekend coverage 80%";
        } else {
            $driverScheduleSettings['batangan'] = [
                'min_schedules' => $batanganSettingsObj->min_schedules,
                'max_schedules' => $batanganSettingsObj->max_schedules,
                'period_days' => $batanganSettingsObj->period_days
            ];
            $messages[] = "Loaded batangan driver settings from database: min {$batanganSettingsObj->min_schedules}, max {$batanganSettingsObj->max_schedules}, period {$batanganSettingsObj->period_days} days";
        }
        
        $batanganSettings = $driverScheduleSettings['batangan'];
        $cadanganSettingsObj = DriverScheduleSettings::getSettingsForType('cadangan');
        if (!$cadanganSettingsObj) {
            Log::warning("No settings found for driver type 'cadangan' in the database. Using default values.");
            $driverScheduleSettings['cadangan'] = [
                'min_schedules' => 10, // Target for backup drivers (85% of batangan)
                'max_schedules' => 11, // Maximum allowed per period 
                'period_days' => 15,   // 15 day period
                'ratio' => 0.85        // 85% of batangan schedules
            ];
            $messages[] = "Using default settings for cadangan drivers: min 10, max 11, period 15 days, batangan ratio 85%";
        } else {
            $driverScheduleSettings['cadangan'] = [
                'min_schedules' => $cadanganSettingsObj->min_schedules,
                'max_schedules' => $cadanganSettingsObj->max_schedules,
                'period_days' => $cadanganSettingsObj->period_days
            ];
            $messages[] = "Loaded cadangan driver settings from database: min {$cadanganSettingsObj->min_schedules}, max {$cadanganSettingsObj->max_schedules}, period {$cadanganSettingsObj->period_days} days";
        }
        
        $cadanganSettings = $driverScheduleSettings['cadangan'];
    }

    public function getUnavailableUnitsForDay(string $dateStr, array $unitDayOffs): array
    {
        return $unitDayOffs[$dateStr] ?? [];
    }

    public function getResourcePercentageFromUnitRenops(string $dateStr, array $renopsSettings): int
    {
        $resourcePercentage = 100;
        $dayOfWeek = Carbon::parse($dateStr)->dayOfWeek;
        $isWeekend = ($dayOfWeek === Carbon::SATURDAY || $dayOfWeek === Carbon::SUNDAY);
        
        if ($isWeekend) {
            $resourcePercentage = $renopsSettings['weekend_percentage'] ?? 80;
        }
        
        $isHoliday = Holiday::where('date', $dateStr)->exists();
        if ($isHoliday) {
            $resourcePercentage = $renopsSettings['holiday_percentage'] ?? 80;
        }
        
        return $resourcePercentage;
    }

    public function createSchedules(array $schedulesToCreate, array &$messages): array
    {
        $successCount = count($schedulesToCreate);
        $failedCount = 0;
        
        if (!empty($schedulesToCreate)) {
            // Verify that all required fields are present in the schedules
            foreach ($schedulesToCreate as $index => $schedule) {
                $requiredFields = ['driver_id', 'route_id', 'unit_id', 'schedule_date', 'shift'];
                $missingFields = [];
                
                foreach ($requiredFields as $field) {
                    if (!isset($schedule[$field]) || empty($schedule[$field])) {
                        $missingFields[] = $field;
                    }
                }
                
                if (!empty($missingFields)) {
                    $messages[] = "Schedule at index {$index} missing required fields: " . implode(', ', $missingFields);
                    Log::warning("Schedule creation skipped due to missing fields: " . implode(', ', $missingFields), $schedule);
                    $failedCount++;
                    $successCount--;
                }
            }
            
            // Filter out invalid schedules
            $validSchedules = array_filter($schedulesToCreate, function($schedule) use ($requiredFields) {
                foreach ($requiredFields as $field) {
                    if (!isset($schedule[$field]) || empty($schedule[$field])) {
                        return false;
                    }
                }
                return true;
            });
            
            if (empty($validSchedules)) {
                $messages[] = "No valid schedules to insert after validation.";
                return [0, $failedCount];
            }
            
            $chunkSize = 50; // Insert 50 records at a time
            $chunks = array_chunk($validSchedules, $chunkSize);
            
            // Use transaction for atomicity
            DB::beginTransaction();
            
            try {
                foreach ($chunks as $index => $chunk) {
                    try {
                        // Add more detailed logging
                        Log::info("Inserting chunk {$index} with " . count($chunk) . " schedules.");
                        
                        Schedule::insert($chunk);
                        
                        Log::info("Successfully inserted chunk {$index}.");
                    } catch (\Exception $e) {
                        // Log the error with more details
                        Log::error("Error inserting schedule chunk {$index}: " . $e->getMessage());
                        Log::error("First schedule in the failed chunk: " . json_encode(reset($chunk)));
                        
                        $messages[] = "ERROR inserting schedules chunk {$index}: " . $e->getMessage();
                        $failedCount += count($chunk);
                        $successCount -= count($chunk);
                        
                        // Re-throw to trigger the transaction rollback
                        throw $e;
                    }
                }
                
                // Commit the transaction if all chunks were successful
                DB::commit();
                $messages[] = "Successfully created {$successCount} schedules.";
            } catch (\Exception $e) {
                // Rollback the transaction if any chunk failed
                DB::rollBack();
                $messages[] = "Transaction rolled back due to errors.";
                Log::error("Schedule insertion transaction rolled back: " . $e->getMessage());
            }
        } else {
            $messages[] = "No schedules were created.";
        }

        if ($failedCount > 0) {
            $messages[] = "Failed to create {$failedCount} schedules.";
            Log::error("Failed to create {$failedCount} schedules.");
        }
        
        return [$successCount, $failedCount];
    }
}
