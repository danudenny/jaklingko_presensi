<?php

namespace App\Services;

use App\Models\Driver;
use App\Models\Route;
use App\Models\Schedule;
use App\Models\Unit;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ScheduleGeneratorService
{
    const BATANGAN_BASE_MAX_SHIFTS = 12;

    const CADANGAN_BASE_MAX_SHIFTS = 12;

    const SHIFT_PAGI = 'pagi';

    const SHIFT_SIANG = 'siang';

    const DRIVER_TYPE_BATANGAN = 'batangan';

    const DRIVER_TYPE_CADANGAN = 'cadangan';

    const STATUS_AKTIF = 'aktif';

    const SCHEDULE_STATUS_SCHEDULED = 'scheduled';

    /**
     * Calculate dynamic max shifts based on schedule period length
     *
     * @param  int  $totalDays  Total days in the schedule period
     * @param  string  $driverType  Driver type (batangan or cadangan)
     * @return int Dynamic max shifts for the period
     */
    private function calculateMaxShifts(int $totalDays, string $driverType): int
    {
        if ($driverType === self::DRIVER_TYPE_BATANGAN) {
            $baseMax = self::BATANGAN_BASE_MAX_SHIFTS;

            // For periods of 16+ days, add 1 to the base max shifts
            // return $totalDays >= 16 ? $baseMax + 1 : $baseMax;
            return $baseMax;
        } else {
            $baseMax = self::CADANGAN_BASE_MAX_SHIFTS;

            // For periods of 16+ days, add 1 to the base max shifts
            // return $totalDays >= 16 ? $baseMax + 1 : $baseMax;
            return $baseMax;
        }
    }

    /**
     * Generate schedules for a given route and date range
     * Can generate for specific unit or all units in the route
     *
     * @param  int|null  $unitId  Optional - if null, generates for all units in the route
     */
    public function generateSchedules(int $routeId, ?int $unitId, string $startDate, string $endDate): array
    {
        try {
            DB::beginTransaction();

            // Validate input
            $validationResult = $this->validateInput($routeId, $unitId, $startDate, $endDate);
            if (! $validationResult['valid']) {
                return [
                    'success' => false,
                    'message' => $validationResult['message'],
                    'data' => [],
                ];
            }

            $route = $validationResult['route'];
            $dateRange = $validationResult['dateRange'];
            $targetUnits = $validationResult['units']; // This will be array of units

            Log::info("Starting schedule generation for route {$routeId}".($unitId ? " and unit {$unitId}" : ' (all units)'));

            // Generate schedules for each target unit
            $allGeneratedSchedules = [];
            $allSkippedDates = [];
            $allErrors = [];
            $allValidationIssues = [];
            $unitResults = [];

            foreach ($targetUnits as $unit) {
                Log::info("=== PROCESSING UNIT {$unit->id} ({$unit->unit_number}) ===");

                // Get available drivers for this unit
                $availableDrivers = $this->getAvailableDrivers($unit->id);

                if ($availableDrivers->isEmpty()) {
                    $unitResults[$unit->id] = [
                        'success' => false,
                        'message' => "Tidak ada driver aktif yang terdaftar untuk unit {$unit->unit_number}",
                        'unit_info' => [
                            'id' => $unit->id,
                            'unit_number' => $unit->unit_number,
                            'status' => $unit->status,
                        ],
                    ];

                    continue;
                }

                // Analyze workload distribution for this unit
                $workloadAnalysis = $this->analyzeWorkloadDistribution($unit->id, $dateRange, $availableDrivers);
                Log::info("Workload analysis for unit {$unit->id}", $workloadAnalysis);

                // Generate schedules for each date for this unit
                $unitGeneratedSchedules = [];
                $unitSkippedDates = [];
                $unitErrors = [];
                $unitValidationIssues = [];

                foreach ($dateRange as $date) {
                    try {
                        $dateString = $date->format('Y-m-d');

                        $dateSchedules = $this->generateSchedulesForDate(
                            $routeId,
                            $unit->id,
                            $date,
                            $availableDrivers,
                            $dateRange
                        );

                        // Validate schedule integrity after generation
                        $integrityIssues = $this->validateScheduleIntegrity($unit->id, $dateString);
                        if (! empty($integrityIssues)) {
                            $unitValidationIssues[$dateString] = $integrityIssues;
                            Log::warning("Schedule integrity issues for unit {$unit->id} on {$dateString}: ".implode(', ', $integrityIssues));
                        }

                        if (! empty($dateSchedules)) {
                            $unitGeneratedSchedules = array_merge($unitGeneratedSchedules, $dateSchedules);
                        } else {
                            $unitSkippedDates[] = $dateString;
                        }
                    } catch (Exception $e) {
                        $unitErrors[] = "Error untuk unit {$unit->unit_number} tanggal {$date->format('Y-m-d')}: ".$e->getMessage();
                        Log::error("Schedule generation error for unit {$unit->id} date {$date->format('Y-m-d')}: ".$e->getMessage());
                    }
                }

                // Calculate coverage statistics for this unit
                $coverageStats = $this->calculateCoverageStatistics($unit->id, $dateRange);

                // Store unit results
                $unitResults[$unit->id] = [
                    'success' => true,
                    'unit_info' => [
                        'id' => $unit->id,
                        'unit_number' => $unit->unit_number,
                        'status' => $unit->status,
                    ],
                    'generated_schedules' => count($unitGeneratedSchedules),
                    'skipped_dates' => $unitSkippedDates,
                    'errors' => $unitErrors,
                    'validation_issues' => $unitValidationIssues,
                    'schedules' => $unitGeneratedSchedules,
                    'coverage_stats' => $coverageStats,
                    'pattern_info' => [
                        'total_days' => count($dateRange),
                        'assignment_type' => 'Simple fixed assignment',
                        'batangan_drivers_used' => $availableDrivers->where('type', self::DRIVER_TYPE_BATANGAN)->count(),
                        'cadangan_drivers_used' => $availableDrivers->where('type', self::DRIVER_TYPE_CADANGAN)->count(),
                        'batangan_max_days' => self::BATANGAN_BASE_MAX_SHIFTS,
                        'cadangan_max_days' => self::CADANGAN_BASE_MAX_SHIFTS,
                    ],
                ];

                // Merge into global results
                $allGeneratedSchedules = array_merge($allGeneratedSchedules, $unitGeneratedSchedules);
                $allSkippedDates = array_merge($allSkippedDates, $unitSkippedDates);
                $allErrors = array_merge($allErrors, $unitErrors);
                $allValidationIssues = array_merge($allValidationIssues, $unitValidationIssues);
            }

            // PHASE 2: Complete incomplete days using ScheduleCompletionService
            Log::info('=== STARTING PHASE 2: SCHEDULE COMPLETION ===');
            $completionService = new \App\Services\ScheduleCompletionService;
            $completionResult = $completionService->completeSchedules($routeId, $unitId, $startDate, $endDate);

            $completionSchedules = [];
            $completionErrors = [];

            if ($completionResult['success']) {
                $completionSchedules = $completionResult['data']['schedules'] ?? [];
                $completionErrors = $completionResult['data']['errors'] ?? [];
                Log::info('Schedule completion added '.count($completionSchedules).' shifts to fill gaps');
            } else {
                $completionErrors[] = 'Schedule completion failed: '.$completionResult['message'];
                Log::error('Schedule completion failed: '.$completionResult['message']);
            }

            DB::commit();

            $totalSchedules = count($allGeneratedSchedules) + count($completionSchedules);
            $totalErrors = array_merge($allErrors, $completionErrors);

            $successMessage = $unitId
                ? "Jadwal berhasil dibuat untuk unit {$unitId} menggunakan two-pass approach: ".count($allGeneratedSchedules).' initial + '.count($completionSchedules)." completion = {$totalSchedules} total shifts"
                : 'Jadwal berhasil dibuat untuk '.count($targetUnits)." unit dalam route {$routeId} menggunakan two-pass approach: ".count($allGeneratedSchedules).' initial + '.count($completionSchedules)." completion = {$totalSchedules} total shifts";

            return [
                'success' => true,
                'message' => $successMessage,
                'data' => [
                    'generated_schedules' => count($allGeneratedSchedules),
                    'completion_schedules' => count($completionSchedules),
                    'total_schedules' => $totalSchedules,
                    'processed_units' => count($targetUnits),
                    'skipped_dates' => $allSkippedDates,
                    'errors' => $totalErrors,
                    'validation_issues' => $allValidationIssues,
                    'schedules' => array_merge($allGeneratedSchedules, $completionSchedules),
                    'unit_results' => $unitResults,
                    'completion_result' => $completionResult,
                    'pattern_info' => [
                        'total_days' => count($dateRange),
                        'pattern_type' => 'Simple fixed assignment: Batangan A->Pagi (12 days max), B->Siang (12 days max), then Cadangan fills remaining',
                        'coverage_strategy' => 'Phase 1: Batangan simple assignment (max 12 days each), Phase 2: Cadangan fills gaps',
                        'batangan_max_shifts' => self::BATANGAN_BASE_MAX_SHIFTS,
                        'cadangan_max_shifts' => self::CADANGAN_BASE_MAX_SHIFTS,
                        'max_shifts_per_day' => 2,
                        'conflict_prevention' => 'Enabled',
                        'completion_enabled' => true,
                    ],
                ],
            ];

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Schedule generation failed: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Terjadi kesalahan saat membuat jadwal: '.$e->getMessage(),
                'data' => [],
            ];
        }
    }

    /**
     * Validate input parameters
     *
     * @param  int|null  $unitId  Optional - if null, validates route and gets all units
     */
    private function validateInput(int $routeId, ?int $unitId, string $startDate, string $endDate): array
    {
        // Validate route exists
        $route = Route::find($routeId);
        if (! $route) {
            return [
                'valid' => false,
                'message' => 'Route tidak ditemukan',
            ];
        }

        // Get target units
        if ($unitId !== null) {
            // Validate specific unit exists
            $unit = Unit::where('id', $unitId)
                ->where('status', self::STATUS_AKTIF)
                ->first();

            if (! $unit) {
                return [
                    'valid' => false,
                    'message' => 'Unit tidak ditemukan atau tidak aktif',
                ];
            }

            // Validate unit has this route
            $hasRoute = $unit->routes()->where('routes.id', $routeId)->exists();
            if (! $hasRoute) {
                return [
                    'valid' => false,
                    'message' => 'Unit tidak memiliki akses ke route yang dipilih',
                ];
            }

            $targetUnits = collect([$unit]);
        } else {
            // Get all active units for this route
            $targetUnits = Unit::where('status', self::STATUS_AKTIF)
                ->whereHas('routes', function ($query) use ($routeId) {
                    $query->where('routes.id', $routeId);
                })
                ->get();

            if ($targetUnits->isEmpty()) {
                return [
                    'valid' => false,
                    'message' => 'Tidak ada unit aktif yang terdaftar untuk route ini',
                ];
            }
        }

        // Validate date range
        try {
            $start = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);

            if ($start->gt($end)) {
                return [
                    'valid' => false,
                    'message' => 'Tanggal mulai tidak boleh lebih besar dari tanggal selesai',
                ];
            }

            $dateRange = CarbonPeriod::create($start, $end)->toArray();

            return [
                'valid' => true,
                'route' => $route,
                'units' => $targetUnits,
                'dateRange' => $dateRange,
            ];

        } catch (Exception $e) {
            return [
                'valid' => false,
                'message' => 'Format tanggal tidak valid',
            ];
        }
    }

    /**
     * Get available drivers for a unit
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getAvailableDrivers(int $unitId)
    {
        return Driver::where('status', self::STATUS_AKTIF)
            ->whereHas('units', function ($query) use ($unitId) {
                $query->where('units.id', $unitId);
            })
            ->orderByRaw('CASE WHEN type = "batangan" THEN 0 ELSE 1 END')
            ->orderBy('name')
            ->get();
    }

    /**
     * Generate schedules for a specific date using simple shift assignment
     * Batangan drivers: max 12 days, assigned to either pagi or siang consistently
     * Cadangan drivers: fill remaining days after batangan limit reached
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $availableDrivers
     */
    private function generateSchedulesForDate(int $routeId, int $unitId, Carbon $date, $availableDrivers, array $dateRange): array
    {
        $schedules = [];
        $dateString = $date->format('Y-m-d');

        Log::info("🚌 === DAILY SCHEDULE GENERATION === {$dateString} ===");

        // Separate drivers by type
        $batanganDrivers = $availableDrivers->where('type', self::DRIVER_TYPE_BATANGAN);
        $cadanganDrivers = $availableDrivers->where('type', self::DRIVER_TYPE_CADANGAN);

        Log::info("Available drivers: {$batanganDrivers->count()} batangan, {$cadanganDrivers->count()} cadangan");

        // Get month boundaries for shift count validation
        $monthStart = $date->copy()->startOfMonth();
        $monthEnd = $date->copy()->endOfMonth();

        // Track which shifts are filled
        $assignedShifts = [];

        // Check for existing schedules before starting
        $existingSchedules = Schedule::where('unit_id', $unitId)
            ->where('schedule_date', $dateString)
            ->get();

        if ($existingSchedules->isNotEmpty()) {
            Log::info("⚠ Found {$existingSchedules->count()} existing schedule(s) for {$dateString}, will work around them");
            foreach ($existingSchedules as $existing) {
                $assignedShifts[$existing->shift] = $existing->driver_id;
                Log::info("Existing: Driver {$existing->driver_id} has {$existing->shift} shift");
            }
        }

        // Phase 1: Assign batangan drivers to their designated shifts (max 12 days each)
        if ($batanganDrivers->count() >= 2) {
            Log::info('🎯 Starting Phase 1: Batangan Simple Assignment (max 12 days each)');
            $batanganSchedules = $this->assignBatanganSimple(
                $routeId, $unitId, $date, $batanganDrivers, $dateRange, $monthStart, $monthEnd, $assignedShifts
            );
            $schedules = array_merge($schedules, $batanganSchedules);
        } else {
            Log::info("⚠ Insufficient batangan drivers ({$batanganDrivers->count()}/2 required), skipping batangan assignment for {$dateString}");
        }

        // Phase 2: Fill remaining slots with cadangan drivers
        Log::info('🔄 Starting Phase 2: Cadangan Driver Fill');
        $cadanganSchedules = $this->fillWithCadanganDrivers(
            $routeId, $unitId, $dateString, $cadanganDrivers, $monthStart, $monthEnd, $assignedShifts
        );
        $schedules = array_merge($schedules, $cadanganSchedules);

        // Final summary
        $finalShiftCount = Schedule::where('unit_id', $unitId)
            ->where('schedule_date', $dateString)
            ->count();

        $coverageStatus = $finalShiftCount >= 2 ? '✅ COMPLETE' : '⚠️ PARTIAL';
        Log::info("🏁 === DAILY GENERATION COMPLETE === {$dateString}: {$finalShiftCount}/2 shifts {$coverageStatus} ===");

        // Validate schedule integrity
        $integrityIssues = $this->validateScheduleIntegrity($unitId, $dateString);
        if (! empty($integrityIssues)) {
            Log::warning("Integrity issues found for {$dateString}: ".implode(', ', $integrityIssues));
        }

        return $schedules;
    }

    /**
     * Simple batangan assignment: Driver A takes pagi for max 12 days, Driver B takes siang for max 12 days
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $batanganDrivers
     */
    private function assignBatanganSimple(int $routeId, int $unitId, Carbon $date, $batanganDrivers, array $dateRange, Carbon $monthStart, Carbon $monthEnd, array &$assignedShifts): array
    {
        $schedules = [];
        $dateString = $date->format('Y-m-d');

        Log::info("=== BATANGAN SIMPLE === Starting simple assignment for {$dateString}");

        // We need at least 2 batangan drivers
        if ($batanganDrivers->count() < 2) {
            Log::warning("⚠ Insufficient batangan drivers ({$batanganDrivers->count()}/2 required) for assignment on {$dateString}");

            return $schedules;
        }

        // Sort drivers consistently
        $sortedDrivers = $batanganDrivers->sortBy('id')->values();
        $driverPagi = $sortedDrivers[0];  // First driver always takes PAGI
        $driverSiang = $sortedDrivers[1]; // Second driver always takes SIANG

        Log::info("Assigned roles: {$driverPagi->name} ({$driverPagi->id}) -> PAGI, {$driverSiang->name} ({$driverSiang->id}) -> SIANG");

        // Check if BOTH drivers should skip this day (only if both have same day off - rare)
        $shouldSkipBatangan = $this->shouldBatanganSkipDay($driverPagi, $driverSiang, $date, $unitId);

        if ($shouldSkipBatangan) {
            Log::info("⏭️ Both batangan drivers skip {$dateString} (".Carbon::parse($dateString)->format('l').') - both have weekend rest day');

            return $schedules;
        }

        // Get weekend off days for individual driver checks
        $dayOfWeek = $date->dayOfWeek;
        $isWeekend = ($dayOfWeek === 0 || $dayOfWeek === 6); // 0 = Sunday, 6 = Saturday
        $driverPagiOffDay = $isWeekend ? $this->getDriverWeekendOffDay($driverPagi->id, $unitId) : -1;
        $driverSiangOffDay = $isWeekend ? $this->getDriverWeekendOffDay($driverSiang->id, $unitId) : -1;

        // Assign PAGI shift to first driver (max 12 days)
        // Skip if this is their weekend off day
        if (! isset($assignedShifts[self::SHIFT_PAGI])) {
            if ($isWeekend && $dayOfWeek === $driverPagiOffDay) {
                Log::info("⏭️ Driver {$driverPagi->name} ({$driverPagi->id}) skips {$dateString} (".Carbon::parse($dateString)->format('l').') - weekend rest day');
            } elseif ($this->canDriverTakeShift($driverPagi, $unitId, $dateString, self::SHIFT_PAGI, $monthStart, $monthEnd)) {
                $conflictCheck = Schedule::where('unit_id', $unitId)
                    ->where('schedule_date', $dateString)
                    ->where('shift', self::SHIFT_PAGI)
                    ->first();

                if (! $conflictCheck) {
                    $schedule = Schedule::create([
                        'route_id' => $routeId,
                        'unit_id' => $unitId,
                        'driver_id' => $driverPagi->id,
                        'schedule_date' => $dateString,
                        'shift' => self::SHIFT_PAGI,
                        'status' => self::SCHEDULE_STATUS_SCHEDULED,
                    ]);
                    $schedules[] = $schedule;
                    $assignedShifts[self::SHIFT_PAGI] = $driverPagi->id;

                    $monthlyCount = Schedule::where('driver_id', $driverPagi->id)
                        ->whereBetween('schedule_date', [
                            $monthStart->format('Y-m-d'),
                            $monthEnd->format('Y-m-d'),
                        ])
                        ->count();

                    Log::info("✓ Batangan PAGI: Driver {$driverPagi->name} ({$driverPagi->id}) assigned on {$dateString} (Monthly: {$monthlyCount}/".self::BATANGAN_BASE_MAX_SHIFTS.')');
                } else {
                    Log::warning("⚠ Conflict: PAGI shift on {$dateString} already taken by driver {$conflictCheck->driver_id}");
                }
            } else {
                Log::info("⚠ Driver {$driverPagi->name} ({$driverPagi->id}) cannot take PAGI shift on {$dateString} (limit reached or other constraint)");
            }
        }

        // Assign SIANG shift to second driver (max 12 days)
        // Skip if this is their weekend off day
        if (! isset($assignedShifts[self::SHIFT_SIANG])) {
            if ($isWeekend && $dayOfWeek === $driverSiangOffDay) {
                Log::info("⏭️ Driver {$driverSiang->name} ({$driverSiang->id}) skips {$dateString} (".Carbon::parse($dateString)->format('l').') - weekend rest day');
            } elseif ($this->canDriverTakeShift($driverSiang, $unitId, $dateString, self::SHIFT_SIANG, $monthStart, $monthEnd)) {
                $conflictCheck = Schedule::where('unit_id', $unitId)
                    ->where('schedule_date', $dateString)
                    ->where('shift', self::SHIFT_SIANG)
                    ->first();

                if (! $conflictCheck) {
                    $schedule = Schedule::create([
                        'route_id' => $routeId,
                        'unit_id' => $unitId,
                        'driver_id' => $driverSiang->id,
                        'schedule_date' => $dateString,
                        'shift' => self::SHIFT_SIANG,
                        'status' => self::SCHEDULE_STATUS_SCHEDULED,
                    ]);
                    $schedules[] = $schedule;
                    $assignedShifts[self::SHIFT_SIANG] = $driverSiang->id;

                    $monthlyCount = Schedule::where('driver_id', $driverSiang->id)
                        ->whereBetween('schedule_date', [
                            $monthStart->format('Y-m-d'),
                            $monthEnd->format('Y-m-d'),
                        ])
                        ->count();

                    Log::info("✓ Batangan SIANG: Driver {$driverSiang->name} ({$driverSiang->id}) assigned on {$dateString} (Monthly: {$monthlyCount}/".self::BATANGAN_BASE_MAX_SHIFTS.')');
                } else {
                    Log::warning("⚠ Conflict: SIANG shift on {$dateString} already taken by driver {$conflictCheck->driver_id}");
                }
            } else {
                Log::info("⚠ Driver {$driverSiang->name} ({$driverSiang->id}) cannot take SIANG shift on {$dateString} (limit reached or other constraint)");
            }
        }

        $currentShiftCount = Schedule::where('unit_id', $unitId)
            ->where('schedule_date', $dateString)
            ->count();

        Log::info("=== BATANGAN SIMPLE COMPLETE === {$dateString}: ".count($schedules)." shifts assigned, total shifts: {$currentShiftCount}/2");

        return $schedules;
    }

    /**
     * Determine if batangan drivers should skip this day (weekend logic)
     * Each driver individually checks their own weekend off day
     * At least one driver should work on each weekend day
     *
     * @param  Driver  $driverPagi
     * @param  Driver  $driverSiang
     * @return bool True if BOTH drivers should skip (never happens - at least one works)
     */
    private function shouldBatanganSkipDay($driverPagi, $driverSiang, Carbon $date, int $unitId): bool
    {
        $dayOfWeek = $date->dayOfWeek; // 0 = Sunday, 6 = Saturday

        // Only check on weekends
        if ($dayOfWeek !== 0 && $dayOfWeek !== 6) {
            return false; // Not a weekend, don't skip
        }

        // Determine which weekend day each driver has off
        $driverPagiOffDay = $this->getDriverWeekendOffDay($driverPagi->id, $unitId);
        $driverSiangOffDay = $this->getDriverWeekendOffDay($driverSiang->id, $unitId);

        // BOTH drivers would need to be off for us to skip the day entirely
        // This should never happen as they're assigned different days
        // We handle individual driver skipping in assignBatanganSimple()
        if ($dayOfWeek === $driverPagiOffDay && $dayOfWeek === $driverSiangOffDay) {
            return true; // Both off - skip day
        }

        return false; // At least one driver works - continue with individual checks
    }

    /**
     * Get the weekend day (0=Sunday, 6=Saturday) that a driver has off
     * Uses deterministic hash to ensure consistency
     *
     * @return int 0 for Sunday, 6 for Saturday
     */
    private function getDriverWeekendOffDay(int $driverId, int $unitId): int
    {
        // Create a deterministic but "random" assignment using hash
        $hash = crc32("driver_{$driverId}_unit_{$unitId}");

        // 0 = Sunday, 6 = Saturday
        return ($hash % 2 === 0) ? 0 : 6;
    }

    /**
     * Fill remaining slots with cadangan drivers
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $cadanganDrivers
     */
    private function fillWithCadanganDrivers(int $routeId, int $unitId, string $dateString, $cadanganDrivers, Carbon $monthStart, Carbon $monthEnd, array &$assignedShifts): array
    {
        $schedules = [];
        $allShifts = [self::SHIFT_PAGI, self::SHIFT_SIANG];

        Log::info("=== CADANGAN PHASE === Starting cadangan driver assignment for {$dateString}");
        Log::info('Currently assigned shifts: '.implode(', ', array_keys($assignedShifts)));

        // Check if we already have complete coverage (2 shifts)
        if (count($assignedShifts) >= 2) {
            Log::info("✓ Day already has complete coverage (2 shifts) from batangan drivers on {$dateString}, cadangan assignment not needed");

            return $schedules;
        }

        // Double check by querying database for existing schedules on this date
        $existingSchedulesCount = Schedule::where('unit_id', $unitId)
            ->where('schedule_date', $dateString)
            ->count();

        if ($existingSchedulesCount >= 2) {
            Log::info("✓ Database shows {$existingSchedulesCount} shifts already exist for {$dateString}, cadangan assignment not needed");

            return $schedules;
        }

        // Get existing shifts from database to merge with in-memory assignments
        $existingShifts = Schedule::where('unit_id', $unitId)
            ->where('schedule_date', $dateString)
            ->pluck('shift')
            ->toArray();

        // Combine in-memory and database shifts to find truly empty slots
        $allAssignedShifts = array_merge($existingShifts, array_keys($assignedShifts));
        $allAssignedShifts = array_unique($allAssignedShifts);

        // Find truly empty shifts
        $emptyShifts = array_diff($allShifts, $allAssignedShifts);

        if (empty($emptyShifts)) {
            Log::info('✓ All shifts already assigned (DB: '.implode(', ', $existingShifts).', Memory: '.implode(', ', array_keys($assignedShifts)).") on {$dateString}");

            return $schedules;
        }

        Log::info('Empty shifts available for cadangan drivers: '.implode(', ', $emptyShifts));
        Log::info('Available cadangan drivers: '.$cadanganDrivers->count());

        // Use 20-day pattern for cadangan drivers
        $currentDate = Carbon::parse($dateString);
        $dayPosition = $monthStart->diffInDays($currentDate) + 1;
        $patternPosition = (($dayPosition - 1) % 20) + 1; // Cycle every 20 days

        Log::info("📅 Cadangan pattern: Day {$patternPosition}/20 for {$dateString}");

        // Sort cadangan drivers for the pattern
        $sortedCadanganDrivers = $cadanganDrivers->sortBy('id')->values();

        foreach ($emptyShifts as $shift) {
            // Before assigning, verify we haven't exceeded the 2-shift limit
            $currentDbShifts = Schedule::where('unit_id', $unitId)
                ->where('schedule_date', $dateString)
                ->count();

            $totalCurrentShifts = $currentDbShifts + count($schedules);

            if ($totalCurrentShifts >= 2) {
                Log::info("⚠ Maximum 2 shifts per day limit reached for {$dateString} (DB: {$currentDbShifts}, New: ".count($schedules).'), stopping cadangan assignment');
                break;
            }

            $assignedDriver = null;

            // Use pattern-based assignment for cadangan drivers
            // Get pattern for this day to determine if cadangan should work
            $pattern = $this->getCadanganPatternForDay($patternPosition);

            // Try to assign based on pattern
            // Pattern defines which shifts are active on each day of the 20-day cycle
            if ($sortedCadanganDrivers->count() >= 2) {
                $cadangan1 = $sortedCadanganDrivers[0];
                $cadangan2 = $sortedCadanganDrivers[1];

                // Determine which cadangan driver should work this shift based on pattern
                $targetDriver = null;

                if ($shift === self::SHIFT_PAGI && $pattern['driver1'] === self::SHIFT_PAGI) {
                    $targetDriver = $cadangan1;
                } elseif ($shift === self::SHIFT_SIANG && $pattern['driver2'] === self::SHIFT_SIANG) {
                    $targetDriver = $cadangan2;
                } elseif ($shift === self::SHIFT_PAGI && $pattern['driver2'] === self::SHIFT_PAGI) {
                    $targetDriver = $cadangan2;
                } elseif ($shift === self::SHIFT_SIANG && $pattern['driver1'] === self::SHIFT_SIANG) {
                    $targetDriver = $cadangan1;
                }

                // Try to assign the target driver
                if ($targetDriver && $this->canDriverTakeShift($targetDriver, $unitId, $dateString, $shift, $monthStart, $monthEnd)) {
                    $conflictCheck = Schedule::where('unit_id', $unitId)
                        ->where('schedule_date', $dateString)
                        ->where('shift', $shift)
                        ->first();

                    if (! $conflictCheck) {
                        $schedule = Schedule::create([
                            'route_id' => $routeId,
                            'unit_id' => $unitId,
                            'driver_id' => $targetDriver->id,
                            'schedule_date' => $dateString,
                            'shift' => $shift,
                            'status' => self::SCHEDULE_STATUS_SCHEDULED,
                        ]);
                        $schedules[] = $schedule;
                        $assignedShifts[$shift] = $targetDriver->id;
                        $assignedDriver = $targetDriver;

                        $monthlyCount = Schedule::where('driver_id', $targetDriver->id)
                            ->whereBetween('schedule_date', [
                                $monthStart->format('Y-m-d'),
                                $monthEnd->format('Y-m-d'),
                            ])
                            ->count();

                        Log::info("✓ Cadangan pattern: Driver {$targetDriver->name} ({$targetDriver->id}) assigned {$shift} shift on {$dateString} (Pattern Day {$patternPosition}/20, Monthly: {$monthlyCount}/".self::CADANGAN_BASE_MAX_SHIFTS.')');
                    }
                }
            }

            // Fallback: If pattern assignment failed, try any available cadangan driver
            if (! $assignedDriver) {
                foreach ($sortedCadanganDrivers as $driver) {
                    if ($this->canDriverTakeShift($driver, $unitId, $dateString, $shift, $monthStart, $monthEnd)) {
                        $conflictCheck = Schedule::where('unit_id', $unitId)
                            ->where('schedule_date', $dateString)
                            ->where('shift', $shift)
                            ->first();

                        if ($conflictCheck) {
                            Log::warning("⚠ Shift conflict detected: {$shift} shift on {$dateString} already taken by driver {$conflictCheck->driver_id}");

                            continue;
                        }

                        $schedule = Schedule::create([
                            'route_id' => $routeId,
                            'unit_id' => $unitId,
                            'driver_id' => $driver->id,
                            'schedule_date' => $dateString,
                            'shift' => $shift,
                            'status' => self::SCHEDULE_STATUS_SCHEDULED,
                        ]);
                        $schedules[] = $schedule;
                        $assignedShifts[$shift] = $driver->id;
                        $assignedDriver = $driver;

                        $monthlyCount = Schedule::where('driver_id', $driver->id)
                            ->whereBetween('schedule_date', [
                                $monthStart->format('Y-m-d'),
                                $monthEnd->format('Y-m-d'),
                            ])
                            ->count();

                        Log::info("✓ Cadangan fallback: Driver {$driver->name} ({$driver->id}) assigned {$shift} shift on {$dateString} (Monthly: {$monthlyCount}/".self::CADANGAN_BASE_MAX_SHIFTS.')');
                        break;
                    } else {
                        Log::debug("⚠ Cadangan constraint: Driver {$driver->name} ({$driver->id}) cannot take {$shift} shift on {$dateString}");
                    }
                }
            }

            if (! $assignedDriver) {
                Log::warning("⚠ No available cadangan driver found for {$shift} shift on {$dateString} (Pattern Day {$patternPosition}/20)");
            }
        }

        Log::info("=== CADANGAN PHASE COMPLETE === {$dateString}: ".count($schedules).' cadangan shifts assigned');

        return $schedules;
    }

    /**
     * Fill any remaining empty slots with available batangan drivers (fallback)
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $batanganDrivers
     */
    private function fillRemainingSlots(int $routeId, int $unitId, string $dateString, $batanganDrivers, Carbon $monthStart, Carbon $monthEnd, array &$assignedShifts): array
    {
        $schedules = [];
        $allShifts = [self::SHIFT_PAGI, self::SHIFT_SIANG];

        Log::info("=== FALLBACK PHASE === Starting batangan fallback assignment for {$dateString}");

        // Check if we already have complete coverage (2 shifts)
        if (count($assignedShifts) >= 2) {
            Log::info("✓ Day already has complete coverage (2 shifts) on {$dateString}, batangan fallback not needed");

            return $schedules;
        }

        // Double check by querying database for existing schedules on this date
        $existingSchedulesCount = Schedule::where('unit_id', $unitId)
            ->where('schedule_date', $dateString)
            ->count();

        if ($existingSchedulesCount >= 2) {
            Log::info("✓ Database shows {$existingSchedulesCount} shifts already exist for {$dateString}, batangan fallback not needed");

            return $schedules;
        }

        // Get existing shifts from database to merge with in-memory assignments
        $existingShifts = Schedule::where('unit_id', $unitId)
            ->where('schedule_date', $dateString)
            ->pluck('shift')
            ->toArray();

        // Combine in-memory and database shifts to find truly empty slots
        $allAssignedShifts = array_merge($existingShifts, array_keys($assignedShifts));
        $allAssignedShifts = array_unique($allAssignedShifts);

        // Find any remaining empty shifts
        $emptyShifts = array_diff($allShifts, $allAssignedShifts);

        if (empty($emptyShifts)) {
            Log::info("✓ All shifts already covered on {$dateString}, fallback not needed");

            return $schedules;
        }

        Log::info('Remaining empty shifts for fallback: '.implode(', ', $emptyShifts));

        // Sort batangan drivers for fair distribution
        $sortedBatanganDrivers = $this->sortDriversForDistribution($batanganDrivers, $monthStart, $monthEnd, $dateString);

        foreach ($emptyShifts as $shift) {
            // Before assigning, verify we haven't exceeded the 2-shift limit
            $currentDbShifts = Schedule::where('unit_id', $unitId)
                ->where('schedule_date', $dateString)
                ->count();

            $totalCurrentShifts = $currentDbShifts + count($schedules);

            if ($totalCurrentShifts >= 2) {
                Log::info("⚠ Maximum 2 shifts per day limit reached for {$dateString} (DB: {$currentDbShifts}, New: ".count($schedules).'), stopping fallback assignment');
                break;
            }

            $assignedDriver = null;
            $rejectionReasons = [];

            // Try to assign to an available batangan driver
            foreach ($sortedBatanganDrivers as $driver) {
                if ($this->canDriverTakeShift($driver, $unitId, $dateString, $shift, $monthStart, $monthEnd)) {
                    // Double check for conflicts before creating
                    $conflictCheck = Schedule::where('unit_id', $unitId)
                        ->where('schedule_date', $dateString)
                        ->where('shift', $shift)
                        ->first();

                    if ($conflictCheck) {
                        Log::warning("⚠ Shift conflict detected: {$shift} shift on {$dateString} already taken by driver {$conflictCheck->driver_id}");

                        continue;
                    }

                    $schedule = Schedule::create([
                        'route_id' => $routeId,
                        'unit_id' => $unitId,
                        'driver_id' => $driver->id,
                        'schedule_date' => $dateString,
                        'shift' => $shift,
                        'status' => self::SCHEDULE_STATUS_SCHEDULED,
                    ]);
                    $schedules[] = $schedule;
                    $assignedShifts[$shift] = $driver->id;
                    $assignedDriver = $driver;

                    // Get driver's current monthly count for logging
                    $monthlyCount = Schedule::where('driver_id', $driver->id)
                        ->whereBetween('schedule_date', [
                            $monthStart->format('Y-m-d'),
                            $monthEnd->format('Y-m-d'),
                        ])
                        ->count();

                    Log::info("✓ Batangan fallback: Driver {$driver->name} ({$driver->id}) assigned {$shift} shift on {$dateString} (Monthly: {$monthlyCount}/".self::BATANGAN_BASE_MAX_SHIFTS.')');
                    break;
                } else {
                    // Track why this driver was rejected for better debugging
                    $rejectionReasons[] = "Driver {$driver->name} ({$driver->id}): validation failed";
                }
            }

            if (! $assignedDriver) {
                Log::warning("⚠ No driver available for {$shift} shift on {$dateString} - slot remains empty");
                if (! empty($rejectionReasons)) {
                    Log::info('Rejection reasons: '.implode(', ', $rejectionReasons));
                }
            }
        }

        $finalShiftCount = Schedule::where('unit_id', $unitId)
            ->where('schedule_date', $dateString)
            ->count();

        Log::info("=== FALLBACK PHASE COMPLETE === {$dateString}: ".count($schedules)." fallback shifts added, total shifts: {$finalShiftCount}/2");

        return $schedules;
    }

    /**
     * Sort drivers for fair distribution based on workload
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $drivers
     */
    private function sortDriversForDistribution($drivers, Carbon $monthStart, Carbon $monthEnd, string $dateString): \Illuminate\Database\Eloquent\Collection
    {
        // Get drivers with their current monthly shift counts
        $driversWithCounts = [];
        foreach ($drivers as $driver) {
            $monthlyCount = Schedule::where('driver_id', $driver->id)
                ->whereBetween('schedule_date', [
                    $monthStart->format('Y-m-d'),
                    $monthEnd->format('Y-m-d'),
                ])
                ->count();

            // Get recent activity to help with rotation
            $recentActivity = Schedule::where('driver_id', $driver->id)
                ->where('schedule_date', '>=', Carbon::parse($dateString)->subDays(7)->format('Y-m-d'))
                ->count();

            $driversWithCounts[] = [
                'driver' => $driver,
                'monthly_count' => $monthlyCount,
                'recent_activity' => $recentActivity,
                'last_shift_date' => $this->getLastShiftDate($driver->id, $dateString),
            ];
        }

        // Sort drivers for better distribution:
        // 1. Fewer monthly shifts first
        // 2. Less recent activity
        // 3. Longer time since last shift
        // 4. Name for consistency
        usort($driversWithCounts, function ($a, $b) {
            // Primary: fewer monthly shifts
            if ($a['monthly_count'] != $b['monthly_count']) {
                return $a['monthly_count'] <=> $b['monthly_count'];
            }

            // Secondary: less recent activity
            if ($a['recent_activity'] != $b['recent_activity']) {
                return $a['recent_activity'] <=> $b['recent_activity'];
            }

            // Tertiary: longer time since last shift
            if ($a['last_shift_date'] != $b['last_shift_date']) {
                return $a['last_shift_date'] <=> $b['last_shift_date'];
            }

            // Final: alphabetical by name for consistency
            return $a['driver']->name <=> $b['driver']->name;
        });

        // Convert back to Eloquent Collection
        $sortedDrivers = collect($driversWithCounts)->pluck('driver');

        return new \Illuminate\Database\Eloquent\Collection($sortedDrivers->all());
    }

    /**
     * Check if a pattern day is a single-shift day (only one driver scheduled)
     *
     * @param  int  $day  Day position (1-20)
     * @return bool True if only one shift is scheduled in the pattern
     */
    private function isSingleShiftPatternDay(int $day): bool
    {
        $pattern = $this->getPatternForDay($day);
        $scheduledDrivers = 0;

        if ($pattern['driver1'] !== '-') {
            $scheduledDrivers++;
        }
        if ($pattern['driver2'] !== '-') {
            $scheduledDrivers++;
        }

        return $scheduledDrivers === 1;
    }

    /**
     * Get the pattern for a specific day (1-20)
     *
     * @param  int  $day  Day position (1-20)
     * @return array Pattern for driver1 and driver2
     */
    private function getPatternForDay(int $day): array
    {
        // Extended Pattern for 20 days:
        // D1  D2  D3  D4  D5  D6  D7  D8  D9  D10 D11 D12 D13 D14 D15 D16 D17 D18 D19 D20
        // S   S   -   P   P   P   P   P   P   P   P   P   P   P   S   S   -   P   P   P   (Driver 1)
        // P   P   P   S   S   S   S   S   S   S   S   S   S   S   -   P   S   S   S   S   (Driver 2)

        $patterns = [
            // First 15 days (original pattern)
            1 => ['driver1' => self::SHIFT_SIANG, 'driver2' => self::SHIFT_PAGI],
            2 => ['driver1' => self::SHIFT_SIANG, 'driver2' => self::SHIFT_PAGI],
            3 => ['driver1' => '-', 'driver2' => self::SHIFT_PAGI],
            4 => ['driver1' => self::SHIFT_PAGI, 'driver2' => self::SHIFT_SIANG],
            5 => ['driver1' => self::SHIFT_PAGI, 'driver2' => self::SHIFT_SIANG],
            6 => ['driver1' => self::SHIFT_PAGI, 'driver2' => self::SHIFT_SIANG],
            7 => ['driver1' => self::SHIFT_PAGI, 'driver2' => self::SHIFT_SIANG],
            8 => ['driver1' => self::SHIFT_PAGI, 'driver2' => self::SHIFT_SIANG],
            9 => ['driver1' => self::SHIFT_PAGI, 'driver2' => self::SHIFT_SIANG],
            10 => ['driver1' => self::SHIFT_PAGI, 'driver2' => self::SHIFT_SIANG],
            11 => ['driver1' => self::SHIFT_PAGI, 'driver2' => self::SHIFT_SIANG],
            12 => ['driver1' => self::SHIFT_PAGI, 'driver2' => self::SHIFT_SIANG],
            13 => ['driver1' => self::SHIFT_PAGI, 'driver2' => self::SHIFT_SIANG],
            14 => ['driver1' => self::SHIFT_PAGI, 'driver2' => self::SHIFT_SIANG],
            15 => ['driver1' => self::SHIFT_SIANG, 'driver2' => '-'],

            // Extended pattern for days 16-20
            16 => ['driver1' => self::SHIFT_SIANG, 'driver2' => self::SHIFT_PAGI],
            17 => ['driver1' => '-', 'driver2' => self::SHIFT_SIANG],
            18 => ['driver1' => self::SHIFT_PAGI, 'driver2' => self::SHIFT_SIANG],
            19 => ['driver1' => self::SHIFT_PAGI, 'driver2' => self::SHIFT_SIANG],
            20 => ['driver1' => self::SHIFT_PAGI, 'driver2' => self::SHIFT_SIANG],
        ];

        return $patterns[$day] ?? ['driver1' => '-', 'driver2' => '-'];
    }

    /**
     * Get the cadangan pattern for a specific day (1-20)
     * This uses the same 20-day pattern as the original batangan pattern
     * Cadangan drivers work 7 days a week following the pattern
     *
     * @param  int  $day  Day position (1-20)
     * @return array Pattern for driver1 and driver2
     */
    private function getCadanganPatternForDay(int $day): array
    {
        // Use the same pattern as batangan used to use
        return $this->getPatternForDay($day);
    }

    /**
     * Get unit-specific pattern offset to prevent conflicts across units
     * This ensures each unit has a different starting point in the 20-day cycle,
     * reducing scheduling conflicts for cadangan drivers who work multiple units.
     *
     * @return int Offset value (0-19)
     */
    private function getUnitPatternOffset(int $unitId): int
    {
        // Use hash-based approach for better distribution across units
        // This ensures consistent offset for same unit across generations
        return abs(crc32("unit_pattern_$unitId")) % 20;
    }

    /**
     * Determine allowed shifts for a specific date based on previous day rules
     */
    private function getAllowedShiftsForDate(int $unitId, Carbon $date, array $dateRange): array
    {
        $previousDay = $date->copy()->subDay();
        $twoDaysAgo = $date->copy()->subDays(2);

        // Check if previous day is within our generation range
        $isPreviousDayInRange = collect($dateRange)->contains(function ($rangeDate) use ($previousDay) {
            return $rangeDate->format('Y-m-d') === $previousDay->format('Y-m-d');
        });

        // Get previous day schedules for this unit
        $previousDaySchedules = Schedule::where('unit_id', $unitId)
            ->where('schedule_date', $previousDay->format('Y-m-d'))
            ->get();

        // Get two days ago schedules for this unit
        $twoDaysAgoSchedules = Schedule::where('unit_id', $unitId)
            ->where('schedule_date', $twoDaysAgo->format('Y-m-d'))
            ->get();

        // Rule 1: If no previous day schedules OR previous day not in our generation range, allow both shifts
        if ($previousDaySchedules->isEmpty() || ! $isPreviousDayInRange) {
            return [self::SHIFT_PAGI, self::SHIFT_SIANG];
        }

        // Rule 2: If previous day had siang shift, current day can only have siang shift
        $hadSiangYesterday = $previousDaySchedules->where('shift', self::SHIFT_SIANG)->isNotEmpty();
        if ($hadSiangYesterday) {
            return [self::SHIFT_SIANG];
        }

        // Rule 3: If previous day only had pagi shift, current day can have both shifts
        $hadPagiYesterday = $previousDaySchedules->where('shift', self::SHIFT_PAGI)->isNotEmpty();
        $hadOnlyPagiYesterday = $hadPagiYesterday && $previousDaySchedules->where('shift', self::SHIFT_SIANG)->isEmpty();

        if ($hadOnlyPagiYesterday) {
            return [self::SHIFT_PAGI, self::SHIFT_SIANG];
        }

        // Rule 4: If two days ago had schedules, allow both shifts
        if ($twoDaysAgoSchedules->isNotEmpty()) {
            return [self::SHIFT_PAGI, self::SHIFT_SIANG];
        }

        // Rule 5: If no schedules exist yet, allow both shifts
        return [self::SHIFT_PAGI, self::SHIFT_SIANG];
    }

    /**
     * Select driver with better distribution logic
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $drivers
     */
    private function selectDriverWithDistribution($drivers, int $unitId, string $dateString, string $shift, Carbon $monthStart, Carbon $monthEnd): ?Driver
    {
        if ($drivers->isEmpty()) {
            return null;
        }

        // Get drivers with their current monthly shift counts
        $driversWithCounts = [];
        foreach ($drivers as $driver) {
            if ($this->canDriverTakeShift($driver, $unitId, $dateString, $shift, $monthStart, $monthEnd)) {
                $monthlyCount = Schedule::where('driver_id', $driver->id)
                    ->whereBetween('schedule_date', [
                        $monthStart->format('Y-m-d'),
                        $monthEnd->format('Y-m-d'),
                    ])
                    ->count();

                // Get recent activity to help with rotation
                $recentActivity = Schedule::where('driver_id', $driver->id)
                    ->where('schedule_date', '>=', Carbon::parse($dateString)->subDays(7)->format('Y-m-d'))
                    ->count();

                $driversWithCounts[] = [
                    'driver' => $driver,
                    'monthly_count' => $monthlyCount,
                    'recent_activity' => $recentActivity,
                    'last_shift_date' => $this->getLastShiftDate($driver->id, $dateString),
                ];
            }
        }

        if (empty($driversWithCounts)) {
            return null;
        }

        // Sort drivers for better distribution:
        // 1. Fewer monthly shifts first
        // 2. Less recent activity
        // 3. Longer time since last shift
        // 4. Name for consistency
        usort($driversWithCounts, function ($a, $b) {
            // Primary: fewer monthly shifts
            if ($a['monthly_count'] != $b['monthly_count']) {
                return $a['monthly_count'] <=> $b['monthly_count'];
            }

            // Secondary: less recent activity
            if ($a['recent_activity'] != $b['recent_activity']) {
                return $a['recent_activity'] <=> $b['recent_activity'];
            }

            // Tertiary: longer time since last shift
            if ($a['last_shift_date'] != $b['last_shift_date']) {
                return $a['last_shift_date'] <=> $b['last_shift_date'];
            }

            // Final: alphabetical by name for consistency
            return $a['driver']->name <=> $b['driver']->name;
        });

        return $driversWithCounts[0]['driver'];
    }

    /**
     * Get the last shift date for a driver (as days ago)
     *
     * @return int Days since last shift (higher = longer ago)
     */
    private function getLastShiftDate(int $driverId, string $currentDate): int
    {
        $lastSchedule = Schedule::where('driver_id', $driverId)
            ->where('schedule_date', '<', $currentDate)
            ->orderBy('schedule_date', 'desc')
            ->first();

        if (! $lastSchedule) {
            return 999; // Very high number if no previous shifts
        }

        $lastDate = Carbon::parse($lastSchedule->schedule_date);
        $current = Carbon::parse($currentDate);

        return $current->diffInDays($lastDate);
    }

    /**
     * Select appropriate driver for a specific shift (legacy method, kept for compatibility)
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $availableDrivers
     */
    private function selectDriverForShift(int $unitId, string $dateString, string $shift, $availableDrivers, Carbon $monthStart, Carbon $monthEnd): ?Driver
    {
        // Use the improved distribution method
        $batanganDrivers = $availableDrivers->where('type', self::DRIVER_TYPE_BATANGAN);
        $cadanganDrivers = $availableDrivers->where('type', self::DRIVER_TYPE_CADANGAN);

        // Try batangan first
        $driver = $this->selectDriverWithDistribution($batanganDrivers, $unitId, $dateString, $shift, $monthStart, $monthEnd);

        // If no batangan available, try cadangan
        if (! $driver) {
            $driver = $this->selectDriverWithDistribution($cadanganDrivers, $unitId, $dateString, $shift, $monthStart, $monthEnd);
        }

        return $driver;
    }

    /**
     * Check if a driver can take a specific shift
     *
     * @param  int|null  $totalDays  Total days in the schedule period (for dynamic max shifts calculation)
     */
    private function canDriverTakeShift(Driver $driver, int $unitId, string $dateString, string $shift, Carbon $monthStart, Carbon $monthEnd, ?int $totalDays = null): bool
    {
        // Rule 1: Driver cannot be scheduled more than 1 shift on the same day (ANY unit)
        $existingScheduleOnDate = Schedule::where('driver_id', $driver->id)
            ->where('schedule_date', $dateString)
            ->exists();

        if ($existingScheduleOnDate) {
            return false;
        }

        // Rule 2: Check if the specific unit+date already has 2 shifts (maximum coverage)
        $existingShiftsOnDateUnit = Schedule::where('unit_id', $unitId)
            ->where('schedule_date', $dateString)
            ->count();

        if ($existingShiftsOnDateUnit >= 2) {
            return false;
        }

        // Rule 3: Driver cannot have duplicate schedules for same date and shift
        $existingScheduleOnShift = Schedule::where('driver_id', $driver->id)
            ->where('schedule_date', $dateString)
            ->where('shift', $shift)
            ->exists();

        if ($existingScheduleOnShift) {
            return false;
        }

        // Rule 4: Check if the specific shift is already taken on this date/unit
        $shiftAlreadyTaken = Schedule::where('unit_id', $unitId)
            ->where('schedule_date', $dateString)
            ->where('shift', $shift)
            ->exists();

        if ($shiftAlreadyTaken) {
            return false;
        }

        // Rule 5: Check monthly shift limits
        $monthlyShiftCount = Schedule::where('driver_id', $driver->id)
            ->whereBetween('schedule_date', [
                $monthStart->format('Y-m-d'),
                $monthEnd->format('Y-m-d'),
            ])
            ->count();

        // Calculate dynamic max shifts based on period length
        if ($totalDays === null) {
            $totalDays = $monthStart->diffInDays($monthEnd) + 1;
        }
        $maxShifts = $this->calculateMaxShifts($totalDays, $driver->type);

        if ($monthlyShiftCount >= $maxShifts) {
            return false;
        }

        // Rule 6: Additional rule for cadangan - cannot be scheduled for multiple shifts in one day
        // (This is redundant with Rule 1, but kept for clarity)
        if ($driver->type === self::DRIVER_TYPE_CADANGAN) {
            $shiftsOnDate = Schedule::where('driver_id', $driver->id)
                ->where('schedule_date', $dateString)
                ->count();

            if ($shiftsOnDate >= 1) {
                return false;
            }
        }

        // Rule 7: Check if driver is on leave (if leave system is implemented)
        if (method_exists($driver, 'leaveRequests')) {
            $onLeave = $driver->leaveRequests()
                ->where('status', 'approved')
                ->where('start_date', '<=', $dateString)
                ->where('end_date', '>=', $dateString)
                ->exists();

            if ($onLeave) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get schedule statistics for a driver in a specific month
     */
    public function getDriverMonthlyStats(int $driverId, Carbon $monthStart, Carbon $monthEnd): array
    {
        $schedules = Schedule::where('driver_id', $driverId)
            ->whereBetween('schedule_date', [
                $monthStart->format('Y-m-d'),
                $monthEnd->format('Y-m-d'),
            ])
            ->get();

        $driver = Driver::find($driverId);
        $remainingShifts = 0;

        if ($driver) {
            $maxShifts = ($driver->type === self::DRIVER_TYPE_BATANGAN)
                ? self::BATANGAN_BASE_MAX_SHIFTS
                : self::CADANGAN_BASE_MAX_SHIFTS;

            $remainingShifts = max(0, $maxShifts - $schedules->count());
        }

        return [
            'total_shifts' => $schedules->count(),
            'pagi_shifts' => $schedules->where('shift', self::SHIFT_PAGI)->count(),
            'siang_shifts' => $schedules->where('shift', self::SHIFT_SIANG)->count(),
            'remaining_shifts' => $remainingShifts,
            'max_shifts' => $driver ? ($driver->type === self::DRIVER_TYPE_BATANGAN
                ? self::BATANGAN_BASE_MAX_SHIFTS
                : self::CADANGAN_BASE_MAX_SHIFTS) : 0,
            'driver_type' => $driver ? $driver->type : null,
        ];
    }

    /**
     * Delete existing schedules for the given parameters before generating new ones
     * Can clear for specific unit or all units in the route
     *
     * @param  int|null  $unitId  Optional - if null, clears for all units in the route
     * @return int Number of deleted schedules
     */
    public function clearExistingSchedules(int $routeId, ?int $unitId, string $startDate, string $endDate): int
    {
        $query = Schedule::where('route_id', $routeId)
            ->whereBetween('schedule_date', [$startDate, $endDate]);

        if ($unitId !== null) {
            $query->where('unit_id', $unitId);
        } else {
            // Clear for all units in this route
            $unitIds = Unit::where('status', self::STATUS_AKTIF)
                ->whereHas('routes', function ($q) use ($routeId) {
                    $q->where('routes.id', $routeId);
                })
                ->pluck('id');

            $query->whereIn('unit_id', $unitIds);
        }

        return $query->delete();
    }

    /**
     * Optimize driver workload distribution across the date range
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $availableDrivers
     * @return array Suggestions for better distribution
     */
    private function analyzeWorkloadDistribution(int $unitId, array $dateRange, $availableDrivers): array
    {
        $analysis = [
            'total_shifts_needed' => 0,
            'total_capacity' => 0,
            'distribution_suggestions' => [],
        ];

        // Calculate total shifts needed (rough estimate)
        $totalDays = count($dateRange);
        $analysis['total_shifts_needed'] = $totalDays * 2; // Assuming 2 shifts per day max

        // Calculate total monthly capacity
        $batanganDrivers = $availableDrivers->where('type', self::DRIVER_TYPE_BATANGAN);
        $cadanganDrivers = $availableDrivers->where('type', self::DRIVER_TYPE_CADANGAN);

        $analysis['total_capacity'] =
            ($batanganDrivers->count() * self::BATANGAN_BASE_MAX_SHIFTS) +
            ($cadanganDrivers->count() * self::CADANGAN_BASE_MAX_SHIFTS);

        // Check if we have enough capacity
        if ($analysis['total_capacity'] < $analysis['total_shifts_needed']) {
            $analysis['distribution_suggestions'][] = 'Warning: Total driver capacity may be insufficient for the requested period';
        }

        // Suggest optimal distribution strategy
        if ($batanganDrivers->count() > 0 && $cadanganDrivers->count() > 0) {
            $analysis['distribution_suggestions'][] = 'Use batangan drivers for primary coverage, cadangan for backup and peak periods';
        } elseif ($batanganDrivers->count() > 0) {
            $analysis['distribution_suggestions'][] = 'Only batangan drivers available - ensure even distribution';
        } elseif ($cadanganDrivers->count() > 0) {
            $analysis['distribution_suggestions'][] = 'Only cadangan drivers available - limited to 11 shifts per driver per month';
        }

        return $analysis;
    }

    /**
     * Get driver workload balance for better scheduling decisions
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $drivers
     */
    private function getDriverWorkloadBalance($drivers, Carbon $monthStart, Carbon $monthEnd): array
    {
        $workloadData = [];

        foreach ($drivers as $driver) {
            $currentSchedules = Schedule::where('driver_id', $driver->id)
                ->whereBetween('schedule_date', [
                    $monthStart->format('Y-m-d'),
                    $monthEnd->format('Y-m-d'),
                ])
                ->count();

            $maxShifts = ($driver->type === self::DRIVER_TYPE_BATANGAN)
                ? self::BATANGAN_BASE_MAX_SHIFTS
                : self::CADANGAN_BASE_MAX_SHIFTS;

            $workloadData[$driver->id] = [
                'driver' => $driver,
                'current_shifts' => $currentSchedules,
                'max_shifts' => $maxShifts,
                'remaining_capacity' => $maxShifts - $currentSchedules,
                'utilization_percentage' => round(($currentSchedules / $maxShifts) * 100, 2),
            ];
        }

        // Sort by utilization percentage (ascending) to prioritize underutilized drivers
        uasort($workloadData, function ($a, $b) {
            return $a['utilization_percentage'] <=> $b['utilization_percentage'];
        });

        return $workloadData;
    }

    /**
     * Validate schedule integrity before and after generation
     */
    private function validateScheduleIntegrity(int $unitId, string $dateString): array
    {
        $issues = [];

        // Check for duplicate drivers on same date
        $duplicateDrivers = Schedule::select('driver_id', \DB::raw('count(*) as count'))
            ->where('unit_id', $unitId)
            ->where('schedule_date', $dateString)
            ->groupBy('driver_id')
            ->having('count', '>', 1)
            ->get();

        if ($duplicateDrivers->isNotEmpty()) {
            foreach ($duplicateDrivers as $duplicate) {
                $issues[] = "Driver {$duplicate->driver_id} has {$duplicate->count} shifts on {$dateString}";
            }
        }

        // Check for duplicate shifts
        $duplicateShifts = Schedule::select('shift', \DB::raw('count(*) as count'))
            ->where('unit_id', $unitId)
            ->where('schedule_date', $dateString)
            ->groupBy('shift')
            ->having('count', '>', 1)
            ->get();

        if ($duplicateShifts->isNotEmpty()) {
            foreach ($duplicateShifts as $duplicate) {
                $issues[] = "Shift {$duplicate->shift} has {$duplicate->count} assignments on {$dateString}";
            }
        }

        // Check for excessive shifts per day
        $totalShifts = Schedule::where('unit_id', $unitId)
            ->where('schedule_date', $dateString)
            ->count();

        if ($totalShifts > 2) {
            $issues[] = "Unit has {$totalShifts} shifts on {$dateString} (maximum: 2)";
        }

        return $issues;
    }

    /**
     * Get detailed schedule summary for a date
     */
    private function getScheduleSummary(int $unitId, string $dateString): array
    {
        $schedules = Schedule::with('driver')
            ->where('unit_id', $unitId)
            ->where('schedule_date', $dateString)
            ->orderBy('shift')
            ->get();

        $summary = [
            'date' => $dateString,
            'total_shifts' => $schedules->count(),
            'coverage_complete' => $schedules->count() >= 2,
            'shifts' => [],
        ];

        foreach ($schedules as $schedule) {
            $summary['shifts'][] = [
                'shift' => $schedule->shift,
                'driver_id' => $schedule->driver_id,
                'driver_name' => $schedule->driver->name ?? 'Unknown',
                'driver_type' => $schedule->driver->type ?? 'Unknown',
            ];
        }

        // Check for missing shifts
        $allShifts = [self::SHIFT_PAGI, self::SHIFT_SIANG];
        $assignedShifts = $schedules->pluck('shift')->toArray();
        $missingShifts = array_diff($allShifts, $assignedShifts);

        $summary['missing_shifts'] = $missingShifts;
        $summary['has_missing_shifts'] = ! empty($missingShifts);

        return $summary;
    }

    /**
     * Calculate coverage statistics for the generated schedules
     */
    private function calculateCoverageStatistics(int $unitId, array $dateRange): array
    {
        $stats = [
            'total_days' => count($dateRange),
            'days_with_full_coverage' => 0,
            'days_with_partial_coverage' => 0,
            'days_with_no_coverage' => 0,
            'total_shifts_generated' => 0,
            'coverage_percentage' => 0,
            'daily_breakdown' => [],
        ];

        foreach ($dateRange as $date) {
            $dateString = $date->format('Y-m-d');
            $shiftsCount = Schedule::where('unit_id', $unitId)
                ->where('schedule_date', $dateString)
                ->count();

            $stats['total_shifts_generated'] += $shiftsCount;
            $stats['daily_breakdown'][$dateString] = [
                'shifts' => $shiftsCount,
                'status' => $shiftsCount >= 2 ? 'full' : ($shiftsCount > 0 ? 'partial' : 'empty'),
            ];

            if ($shiftsCount >= 2) {
                $stats['days_with_full_coverage']++;
            } elseif ($shiftsCount > 0) {
                $stats['days_with_partial_coverage']++;
            } else {
                $stats['days_with_no_coverage']++;
            }
        }

        // Calculate coverage percentage (based on 2 shifts per day target)
        $maxPossibleShifts = count($dateRange) * 2;
        $stats['coverage_percentage'] = $maxPossibleShifts > 0
            ? round(($stats['total_shifts_generated'] / $maxPossibleShifts) * 100, 2)
            : 0;

        return $stats;
    }

    /**
     * Calculate coverage statistics for multiple units
     *
     * @param  array  $unitIds  Array of unit IDs
     */
    public function calculateMultiUnitCoverageStatistics(array $unitIds, array $dateRange): array
    {
        $overallStats = [
            'total_days' => count($dateRange),
            'total_units' => count($unitIds),
            'total_possible_shifts' => count($dateRange) * count($unitIds) * 2, // 2 shifts per day per unit
            'total_generated_shifts' => 0,
            'coverage_percentage' => 0,
            'unit_breakdown' => [],
        ];

        foreach ($unitIds as $unitId) {
            $unitStats = $this->calculateCoverageStatistics($unitId, $dateRange);
            $overallStats['unit_breakdown'][$unitId] = $unitStats;
            $overallStats['total_generated_shifts'] += $unitStats['total_schedules'] ?? 0;
        }

        if ($overallStats['total_possible_shifts'] > 0) {
            $overallStats['coverage_percentage'] = round(
                ($overallStats['total_generated_shifts'] / $overallStats['total_possible_shifts']) * 100,
                2
            );
        }

        return $overallStats;
    }

    /**
     * Get summary for route-wide schedule generation
     */
    public function getRouteGenerationSummary(int $routeId, array $unitResults, array $dateRange): array
    {
        $summary = [
            'route_id' => $routeId,
            'total_units_processed' => count($unitResults),
            'successful_units' => 0,
            'failed_units' => 0,
            'total_schedules_generated' => 0,
            'total_errors' => 0,
            'units_with_issues' => 0,
            'pattern_offset_distribution' => [],
        ];

        foreach ($unitResults as $unitId => $result) {
            if ($result['success']) {
                $summary['successful_units']++;
                $summary['total_schedules_generated'] += $result['generated_schedules'];

                // Track pattern offset distribution
                if (isset($result['pattern_info']['unit_pattern_offset'])) {
                    $offset = $result['pattern_info']['unit_pattern_offset'];
                    $summary['pattern_offset_distribution'][$offset] = ($summary['pattern_offset_distribution'][$offset] ?? 0) + 1;
                }
            } else {
                $summary['failed_units']++;
            }

            if (! empty($result['errors']) || ! empty($result['validation_issues'])) {
                $summary['units_with_issues']++;
                $summary['total_errors'] += count($result['errors'] ?? []);
            }
        }

        return $summary;
    }
}
