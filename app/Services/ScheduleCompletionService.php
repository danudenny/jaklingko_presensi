<?php

namespace App\Services;

use App\Models\Schedule;
use App\Models\Unit;
use App\Models\Driver;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class ScheduleCompletionService
{
    const SHIFT_PAGI = 'pagi';
    const SHIFT_SIANG = 'siang';
    const DRIVER_TYPE_BATANGAN = 'batangan';
    const DRIVER_TYPE_CADANGAN = 'cadangan';
    const STATUS_AKTIF = 'aktif';
    const SCHEDULE_STATUS_SCHEDULED = 'scheduled';
    const BATANGAN_BASE_MAX_SHIFTS = 12;
    const CADANGAN_BASE_MAX_SHIFTS = 12;

    /**
     * Complete missing shifts for incomplete days in a date range
     *
     * @param int $routeId
     * @param int|null $unitId Optional - if null, processes all units in the route
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function completeSchedules(int $routeId, ?int $unitId, string $startDate, string $endDate): array
    {
        try {
            DB::beginTransaction();

            // Get target units
            if ($unitId !== null) {
                $targetUnits = Unit::where('id', $unitId)
                    ->where('status', self::STATUS_AKTIF)
                    ->get();
            } else {
                $targetUnits = Unit::where('status', self::STATUS_AKTIF)
                    ->whereHas('routes', function($query) use ($routeId) {
                        $query->where('routes.id', $routeId);
                    })
                    ->get();
            }

            if ($targetUnits->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'Tidak ada unit yang ditemukan',
                    'data' => []
                ];
            }

            // Create date range
            $start = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);
            $dateRange = CarbonPeriod::create($start, $end)->toArray();

            $allCompletedSchedules = [];
            $allErrors = [];
            $unitResults = [];

            foreach ($targetUnits as $unit) {
                Log::info("=== COMPLETING SCHEDULES FOR UNIT {$unit->id} ({$unit->unit_number}) ===");
                
                $unitCompletedSchedules = [];
                $unitErrors = [];
                $incompleteDays = [];

                // Get available drivers for this unit
                $availableDrivers = $this->getAvailableDrivers($unit->id);
                
                if ($availableDrivers->isEmpty()) {
                    $unitResults[$unit->id] = [
                        'success' => false,
                        'message' => "Tidak ada driver aktif untuk unit {$unit->unit_number}",
                        'unit_info' => [
                            'id' => $unit->id,
                            'unit_number' => $unit->unit_number
                        ]
                    ];
                    continue;
                }

                // Find incomplete days (days with less than 2 shifts)
                foreach ($dateRange as $date) {
                    $dateString = $date->format('Y-m-d');
                    
                    $existingShifts = Schedule::where('unit_id', $unit->id)
                        ->where('schedule_date', $dateString)
                        ->count();
                    
                    if ($existingShifts < 2) {
                        $incompleteDays[] = $dateString;
                        Log::info("Found incomplete day: {$dateString} has {$existingShifts}/2 shifts");
                    }
                }

                Log::info("Found " . count($incompleteDays) . " incomplete days for unit {$unit->unit_number}");

                // Complete each incomplete day
                foreach ($incompleteDays as $dateString) {
                    try {
                        $completedSchedules = $this->completeDay(
                            $routeId, 
                            $unit->id, 
                            $dateString, 
                            $availableDrivers
                        );
                        
                        $unitCompletedSchedules = array_merge($unitCompletedSchedules, $completedSchedules);
                        
                        if (!empty($completedSchedules)) {
                            Log::info("✓ Completed {$dateString} with " . count($completedSchedules) . " additional shifts");
                        }
                    } catch (Exception $e) {
                        $unitErrors[] = "Error completing {$dateString}: " . $e->getMessage();
                        Log::error("Error completing day {$dateString} for unit {$unit->id}: " . $e->getMessage());
                    }
                }

                // Store unit results
                $unitResults[$unit->id] = [
                    'success' => true,
                    'unit_info' => [
                        'id' => $unit->id,
                        'unit_number' => $unit->unit_number
                    ],
                    'incomplete_days_found' => count($incompleteDays),
                    'schedules_added' => count($unitCompletedSchedules),
                    'errors' => $unitErrors,
                    'schedules' => $unitCompletedSchedules
                ];

                $allCompletedSchedules = array_merge($allCompletedSchedules, $unitCompletedSchedules);
                $allErrors = array_merge($allErrors, $unitErrors);
            }

            DB::commit();

            return [
                'success' => true,
                'message' => "Schedule completion finished. Added " . count($allCompletedSchedules) . " shifts to complete incomplete days.",
                'data' => [
                    'completed_schedules' => count($allCompletedSchedules),
                    'processed_units' => count($targetUnits),
                    'errors' => $allErrors,
                    'schedules' => $allCompletedSchedules,
                    'unit_results' => $unitResults
                ]
            ];

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Schedule completion failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Terjadi kesalahan saat melengkapi jadwal: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }

    /**
     * Complete a single day by filling missing shifts
     *
     * @param int $routeId
     * @param int $unitId
     * @param string $dateString
     * @param \Illuminate\Database\Eloquent\Collection $availableDrivers
     * @return array
     */
    private function completeDay(int $routeId, int $unitId, string $dateString, $availableDrivers): array
    {
        $schedules = [];
        $allShifts = [self::SHIFT_PAGI, self::SHIFT_SIANG];
        
        Log::info("🔧 COMPLETING DAY: {$dateString}");

        // Get existing shifts for this day
        $existingShifts = Schedule::where('unit_id', $unitId)
            ->where('schedule_date', $dateString)
            ->pluck('shift')
            ->toArray();

        // Find missing shifts
        $missingShifts = array_diff($allShifts, $existingShifts);
        
        if (empty($missingShifts)) {
            Log::info("✓ Day {$dateString} is already complete");
            return $schedules;
        }

        Log::info("Missing shifts for {$dateString}: " . implode(', ', $missingShifts));

        // Get month boundaries for shift count validation
        $date = Carbon::parse($dateString);
        $monthStart = $date->copy()->startOfMonth();
        $monthEnd = $date->copy()->endOfMonth();

        // Separate drivers by type
        $cadanganDrivers = $availableDrivers->where('type', self::DRIVER_TYPE_CADANGAN);

        // Try to fill each missing shift with CADANGAN drivers ONLY
        // Batangan drivers should already be assigned to their designated shifts in Phase 1
        foreach ($missingShifts as $shift) {
            $assignedDriver = null;

            // Try cadangan drivers to fill gaps
            Log::info("🎯 Trying cadangan drivers for {$shift} shift on {$dateString}");
            $sortedCadanganDrivers = $this->sortDriversForDistribution($cadanganDrivers, $monthStart, $monthEnd, $dateString);
            
            foreach ($sortedCadanganDrivers as $driver) {
                if ($this->canDriverTakeShift($driver, $unitId, $dateString, $shift, $monthStart, $monthEnd)) {
                    $schedule = Schedule::create([
                        'route_id' => $routeId,
                        'unit_id' => $unitId,
                        'driver_id' => $driver->id,
                        'schedule_date' => $dateString,
                        'shift' => $shift,
                        'status' => self::SCHEDULE_STATUS_SCHEDULED
                    ]);
                    
                    $schedules[] = $schedule;
                    $assignedDriver = $driver;
                    
                    $monthlyCount = Schedule::where('driver_id', $driver->id)
                        ->whereBetween('schedule_date', [
                            $monthStart->format('Y-m-d'),
                            $monthEnd->format('Y-m-d')
                        ])
                        ->count();
                    
                    Log::info("✓ COMPLETION: Cadangan driver {$driver->name} ({$driver->id}) assigned {$shift} shift on {$dateString} (Monthly: {$monthlyCount}/" . self::CADANGAN_BASE_MAX_SHIFTS . ")");
                    break;
                }
            }

            // If no driver available, log warning but don't force assignment
            if (!$assignedDriver) {
                Log::warning("⚠️ COMPLETION: No cadangan driver available for {$shift} shift on {$dateString} - shift remains empty");
            }
        }

        $finalShiftCount = Schedule::where('unit_id', $unitId)
            ->where('schedule_date', $dateString)
            ->count();
        
        $status = $finalShiftCount >= 2 ? "✅ COMPLETE" : "⚠️ STILL INCOMPLETE";
        Log::info("🏁 Day completion result for {$dateString}: {$finalShiftCount}/2 shifts {$status}");

        return $schedules;
    }

    /**
     * Get available drivers for a unit
     */
    private function getAvailableDrivers(int $unitId)
    {
        return Driver::where('status', self::STATUS_AKTIF)
            ->whereHas('units', function($query) use ($unitId) {
                $query->where('units.id', $unitId);
            })
            ->orderByRaw("CASE WHEN type = 'cadangan' THEN 0 ELSE 1 END") // Prioritize cadangan for completion
            ->orderBy('name')
            ->get();
    }

    /**
     * Sort drivers for fair distribution
     */
    private function sortDriversForDistribution($drivers, Carbon $monthStart, Carbon $monthEnd, string $dateString)
    {
        $driversWithCounts = [];
        
        foreach ($drivers as $driver) {
            $monthlyCount = Schedule::where('driver_id', $driver->id)
                ->whereBetween('schedule_date', [
                    $monthStart->format('Y-m-d'),
                    $monthEnd->format('Y-m-d')
                ])
                ->count();

            $recentActivity = Schedule::where('driver_id', $driver->id)
                ->where('schedule_date', '>=', Carbon::parse($dateString)->subDays(7)->format('Y-m-d'))
                ->count();

            $driversWithCounts[] = [
                'driver' => $driver,
                'monthly_count' => $monthlyCount,
                'recent_activity' => $recentActivity
            ];
        }

        // Sort by monthly count (fewer first), then recent activity (less first), then name
        usort($driversWithCounts, function($a, $b) {
            if ($a['monthly_count'] != $b['monthly_count']) {
                return $a['monthly_count'] <=> $b['monthly_count'];
            }
            if ($a['recent_activity'] != $b['recent_activity']) {
                return $a['recent_activity'] <=> $b['recent_activity'];
            }
            return $a['driver']->name <=> $b['driver']->name;
        });

        return collect($driversWithCounts)->pluck('driver');
    }

    /**
     * Check if driver can take a shift with normal constraints
     */
    private function canDriverTakeShift(Driver $driver, int $unitId, string $dateString, string $shift, Carbon $monthStart, Carbon $monthEnd): bool
    {
        // Check if driver already has a shift on this date
        $existingShift = Schedule::where('driver_id', $driver->id)
            ->where('schedule_date', $dateString)
            ->first();
        
        if ($existingShift) {
            return false;
        }

        // Check monthly limits
        $monthlyCount = Schedule::where('driver_id', $driver->id)
            ->whereBetween('schedule_date', [
                $monthStart->format('Y-m-d'),
                $monthEnd->format('Y-m-d')
            ])
            ->count();

        $maxShifts = $driver->type === self::DRIVER_TYPE_BATANGAN 
            ? self::BATANGAN_BASE_MAX_SHIFTS 
            : self::CADANGAN_BASE_MAX_SHIFTS;

        if ($monthlyCount >= $maxShifts) {
            return false;
        }

        // Check shift sequence rules
        return $this->checkShiftSequenceRules($driver->id, $unitId, $dateString, $shift);
    }

    /**
     * Check shift sequence rules
     */
    private function checkShiftSequenceRules(int $driverId, int $unitId, string $dateString, string $shift): bool
    {
        $previousDay = Carbon::parse($dateString)->subDay();
        
        $previousDaySchedule = Schedule::where('driver_id', $driverId)
            ->where('schedule_date', $previousDay->format('Y-m-d'))
            ->first();

        if (!$previousDaySchedule) {
            return true; // No previous day constraint
        }

        // If yesterday was 'siang', today can only be 'siang'
        if ($previousDaySchedule->shift === self::SHIFT_SIANG && $shift === self::SHIFT_PAGI) {
            return false;
        }

        return true;
    }
}
